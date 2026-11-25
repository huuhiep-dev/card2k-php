<?php

declare(strict_types=1);

namespace Card2K\Http;

use Card2K\ErrorCode;
use Card2K\Exception\ApiException;
use Card2K\Exception\AuthenticationException;
use Card2K\Exception\BadRequestException;
use Card2K\Exception\ConflictException;
use Card2K\Exception\InsufficientBalanceException;
use Card2K\Exception\LockedException;
use Card2K\Exception\NetworkException;
use Card2K\Exception\NotFoundException;
use Card2K\Exception\PermissionException;
use Card2K\Exception\RateLimitException;
use Card2K\Exception\ServerException;
use Card2K\Exception\ServiceUnavailableException;
use Card2K\Exception\ValidationException;
use Closure;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Gửi request tới API v3: thêm key và header, thử lại khi nên thử, đổi lỗi thành exception.
 *
 * Thử lại (tối đa `maxRetries` lần, cùng request nên cùng Idempotency-Key): không kết nối được,
 * HTTP 429 / 500 / 502 / 503 / 504, và 409 `request_in_progress`. Chờ theo `Retry-After` nếu có
 * (quá `maxRetryDelay` giây thì không chờ, ném lỗi luôn), không thì 0,5 – 1 – 2 – … giây.
 * Mọi lệnh ghi của API v3 đều chống trùng (Idempotency-Key, reference) nên thử lại là an toàn.
 *
 * @internal dùng qua {@see \Card2K\Client}
 */
final class Transport
{
    private const RETRY_STATUSES = [429, 500, 502, 503, 504];

    private ?ResponseInterface $lastResponse = null;

    /**
     * @param  Closure(float): void  $sleep
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly ClientInterface $http,
        private readonly RequestFactoryInterface $requests,
        private readonly StreamFactoryInterface $streams,
        private readonly int $maxRetries,
        private readonly float $maxRetryDelay,
        private readonly Closure $sleep,
        private readonly string $userAgent,
    ) {
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @param  array<string, mixed>|null  $body
     * @param  array<string, string>  $headers
     * @return array<string, mixed> phản hồi JSON đã giải mã
     *
     * @throws ApiException
     * @throws NetworkException
     */
    public function send(string $method, string $path, array $query = [], ?array $body = null, array $headers = []): array
    {
        $request = $this->build($method, $path, $query, $body, $headers);

        for ($attempt = 0; ; $attempt++) {
            try {
                $response = $this->http->sendRequest($request);
            } catch (ClientExceptionInterface $exception) {
                $retryable = $exception instanceof NetworkExceptionInterface || ! $exception instanceof RequestExceptionInterface;
                if ($retryable && $attempt < $this->maxRetries) {
                    ($this->sleep)($this->backoff($attempt));

                    continue;
                }

                throw new NetworkException('Không kết nối được tới API: '.$exception->getMessage(), $request, $exception);
            }

            $this->lastResponse = $response;
            $status = $response->getStatusCode();

            if ($status >= 200 && $status < 300) {
                return $this->decodeSuccess($response);
            }

            $error = $this->exceptionFor($response);

            if ($attempt < $this->maxRetries && $this->shouldRetry($status, $error->getErrorCode())) {
                $delay = $this->retryDelay($response, $attempt);
                if ($delay !== null) {
                    ($this->sleep)($delay);

                    continue;
                }
            }

            throw $error;
        }
    }

    /**
     * Phản hồi gần nhất (kể cả lỗi), để xem mã HTTP (201 tạo mới / 200 gửi lại) hay header.
     */
    public function lastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @param  array<string, mixed>|null  $body
     * @param  array<string, string>  $headers
     */
    private function build(string $method, string $path, array $query, ?array $body, array $headers): RequestInterface
    {
        $query = array_filter($query, static fn ($value): bool => $value !== null);
        $url = $this->baseUrl.$path.($query === [] ? '' : '?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986));

        $request = $this->requests->createRequest($method, $url)
            ->withHeader('Authorization', 'Bearer '.$this->apiKey)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', $this->userAgent);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streams->createStream($json));
        }

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSuccess(ResponseInterface $response): array
    {
        $decoded = $this->decode($response);

        if ($decoded === null) {
            throw new ApiException(
                'API trả về dữ liệu không phải JSON (HTTP '.$response->getStatusCode().').',
                $response->getStatusCode(),
                ErrorCode::HTTP_ERROR,
                [],
                $this->requestId($response),
                $response,
            );
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decode(ResponseInterface $response): ?array
    {
        try {
            $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        $result = [];
        foreach ($decoded as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    private function exceptionFor(ResponseInterface $response): ApiException
    {
        $status = $response->getStatusCode();
        $error = $this->decode($response)['error'] ?? null;

        $code = ErrorCode::HTTP_ERROR;
        $message = 'API trả lỗi HTTP '.$status.'.';
        $details = [];

        if (is_array($error)) {
            $code = is_string($error['code'] ?? null) ? $error['code'] : $code;
            $message = is_string($error['message'] ?? null) ? $error['message'] : $message;
            foreach ($error as $key => $value) {
                if ($key !== 'code' && $key !== 'message') {
                    $details[(string) $key] = $value;
                }
            }
        }

        $class = match (true) {
            $status === 400 => BadRequestException::class,
            $status === 401 => AuthenticationException::class,
            $status === 402 => InsufficientBalanceException::class,
            $status === 403 => PermissionException::class,
            $status === 404 => NotFoundException::class,
            $status === 409 => ConflictException::class,
            $status === 422 => ValidationException::class,
            $status === 423 => LockedException::class,
            $status === 429 => RateLimitException::class,
            $status === 503 => ServiceUnavailableException::class,
            $status >= 500 => ServerException::class,
            default => ApiException::class,
        };

        return new $class($message, $status, $code, $details, $this->requestId($response), $response);
    }

    private function shouldRetry(int $status, string $code): bool
    {
        return in_array($status, self::RETRY_STATUSES, true)
            || ($status === 409 && $code === ErrorCode::REQUEST_IN_PROGRESS);
    }

    /**
     * Số giây chờ trước lần thử sau, hoặc null nếu `Retry-After` xa hơn `maxRetryDelay`.
     */
    private function retryDelay(ResponseInterface $response, int $attempt): ?float
    {
        $header = trim($response->getHeaderLine('Retry-After'));

        if ($header === '') {
            return $this->backoff($attempt);
        }

        if (ctype_digit($header)) {
            $seconds = (float) $header;
        } else {
            $time = strtotime($header);
            $seconds = $time === false ? null : max(0.0, (float) ($time - time()));
        }

        if ($seconds === null) {
            return $this->backoff($attempt);
        }

        return $seconds <= $this->maxRetryDelay ? $seconds : null;
    }

    private function backoff(int $attempt): float
    {
        $base = min(0.5 * (2 ** $attempt), 8.0);

        return $base * (random_int(75, 125) / 100);
    }

    private function requestId(ResponseInterface $response): ?string
    {
        $id = $response->getHeaderLine('X-Request-Id');

        return $id === '' ? null : $id;
    }
}
