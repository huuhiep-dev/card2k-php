<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 409: `request_in_progress` (cùng Idempotency-Key đang được xử lý; SDK tự thử lại) hoặc
 * `warranty_claim_open` (đơn đã có yêu cầu bảo hành đang mở, xem `claim_id`).
 */
final class ConflictException extends ApiException
{
}
