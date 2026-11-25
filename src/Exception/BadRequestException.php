<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 400: thiếu header bắt buộc, ví dụ `idempotency_key_required`.
 */
final class BadRequestException extends ApiException
{
}
