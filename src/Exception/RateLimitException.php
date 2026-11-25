<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 429 `rate_limited`: gọi quá nhanh. SDK đã tự chờ và thử lại trước khi ném lỗi này.
 */
final class RateLimitException extends ApiException
{
    /**
     * Số giây nên chờ (header `Retry-After`), hoặc null.
     */
    public function retryAfter(): ?int
    {
        $header = $this->getResponse()?->getHeaderLine('Retry-After') ?? '';

        return ctype_digit($header) ? (int) $header : null;
    }
}
