<?php

declare(strict_types=1);

namespace Card2K\Exception;

use Psr\Http\Message\RequestInterface;
use Throwable;

/**
 * Không nhận được câu trả lời (DNS, không kết nối được, hết thời gian chờ), kể cả sau khi SDK
 * đã tự thử lại.
 *
 * Shop có thể đã làm hoặc chưa. Gửi lại đúng lệnh đó với cùng Idempotency-Key / reference:
 * shop chỉ làm đúng một lần.
 */
final class NetworkException extends Card2KException
{
    public function __construct(
        string $message,
        private readonly RequestInterface $request,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Idempotency-Key của request (để gửi lại), hoặc null nếu lệnh không có.
     */
    public function getIdempotencyKey(): ?string
    {
        $key = $this->request->getHeaderLine('Idempotency-Key');

        return $key === '' ? null : $key;
    }
}
