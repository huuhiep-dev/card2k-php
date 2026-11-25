<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 5xx khác: lỗi phía shop. Gửi lại cùng Idempotency-Key / reference là an toàn.
 */
final class ServerException extends ApiException
{
}
