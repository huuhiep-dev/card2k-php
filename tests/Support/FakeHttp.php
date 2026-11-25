<?php

declare(strict_types=1);

namespace Card2K\Tests\Support;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Client PSR-18 giả: trả lần lượt các phản hồi (hoặc ném lỗi) đã xếp, ghi lại mọi request.
 */
final class FakeHttp implements ClientInterface
{
    /** @var list<ResponseInterface|Throwable> */
    private array $queue = [];

    /** @var list<RequestInterface> */
    public array $requests = [];

    /**
     * @param  array<mixed>  $body
     * @param  array<string, string>  $headers
     */
    public function json(int $status, array $body, array $headers = []): self
    {
        $this->queue[] = new Response($status, $headers + ['Content-Type' => 'application/json', 'X-Request-Id' => 'req-'.count($this->queue)], (string) json_encode($body));

        return $this;
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function raw(int $status, string $body, array $headers = []): self
    {
        $this->queue[] = new Response($status, $headers, $body);

        return $this;
    }

    public function fail(Throwable $exception): self
    {
        $this->queue[] = $exception;

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $next = array_shift($this->queue);

        if ($next === null) {
            throw new RuntimeException('FakeHttp: không còn phản hồi nào cho '.$request->getMethod().' '.$request->getUri());
        }
        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next;
    }

    public function last(): RequestInterface
    {
        $last = $this->requests[count($this->requests) - 1] ?? null;
        if ($last === null) {
            throw new RuntimeException('FakeHttp: chưa có request nào.');
        }

        return $last;
    }

    /**
     * Body JSON của request cuối.
     *
     * @return array<mixed>
     */
    public function lastBody(): array
    {
        $decoded = json_decode((string) $this->last()->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
