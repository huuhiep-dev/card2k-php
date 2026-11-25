<?php

declare(strict_types=1);

namespace Card2K\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * API trả lỗi: `{ "error": { "code", "message", … } }`.
 *
 * Xử lý theo {@see getErrorCode()} (ổn định, xem {@see \Card2K\ErrorCode}), không theo câu chữ:
 * `message` viết cho người đọc và có thể đổi.
 */
class ApiException extends Card2KException
{
    /**
     * @param  array<string, mixed>  $details  các field khác của `error` (`fields`, `required`, …)
     */
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly string $errorCode,
        private readonly array $details,
        private readonly ?string $requestId,
        private readonly ?ResponseInterface $response = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    /**
     * Mã HTTP.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * `error.code`, ví dụ `insufficient_balance`.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Các field khác của `error`, ví dụ `['required' => 50000, 'balance' => 20000]`.
     *
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Một field của `error`, hoặc null.
     */
    public function getDetail(string $key): mixed
    {
        return $this->details[$key] ?? null;
    }

    /**
     * Header `X-Request-Id` của phản hồi: gửi kèm khi hỏi shop về lỗi này.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
