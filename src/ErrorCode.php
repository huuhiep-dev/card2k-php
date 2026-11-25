<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Các giá trị `error.code` API v3 đang trả. Shop có thể thêm mã mới (không bỏ mã cũ), nên code
 * của bạn nên có nhánh mặc định cho mã lạ.
 *
 * ```php
 * catch (ApiException $e) {
 *     if ($e->getErrorCode() === ErrorCode::INSUFFICIENT_BALANCE) { … }
 * }
 * ```
 */
final class ErrorCode
{
    // Xác thực, quyền (401, 403)
    public const MISSING_API_KEY = 'missing_api_key';

    public const INVALID_API_KEY = 'invalid_api_key';

    public const WRONG_CONNECTION_TYPE = 'wrong_connection_type';

    public const CONNECTION_DISABLED = 'connection_disabled';

    public const CONNECTION_LOCKED = 'connection_locked';

    public const ACCOUNT_BLOCKED = 'account_blocked';

    public const IP_NOT_ALLOWED = 'ip_not_allowed';

    public const SCOPE_NOT_GRANTED = 'scope_not_granted';

    public const PHONE_VERIFICATION_REQUIRED = 'phone_verification_required';

    // Gửi lại, chống trùng (400, 409, 422)
    public const IDEMPOTENCY_KEY_REQUIRED = 'idempotency_key_required';

    public const IDEMPOTENCY_KEY_REUSED = 'idempotency_key_reused';

    public const REQUEST_IN_PROGRESS = 'request_in_progress';

    public const REFERENCE_REUSED = 'reference_reused';

    // Dữ liệu (422)
    public const VALIDATION_FAILED = 'validation_failed';

    public const INVALID_FIELDS = 'invalid_fields';

    public const INVALID_QUEUE = 'invalid_queue';

    public const QUANTITY_TOO_LARGE = 'quantity_too_large';

    public const VOUCHER_REFUSED = 'voucher_refused';

    // Đặt đơn
    public const INSUFFICIENT_BALANCE = 'insufficient_balance';

    public const PRODUCT_UNAVAILABLE = 'product_unavailable';

    public const PACKAGE_UNAVAILABLE = 'package_unavailable';

    public const PRICE_CHANGED = 'price_changed';

    public const ORDER_REFUSED = 'order_refused';

    // Phí ưu tiên Sò hàng chờ
    public const PRIORITY_NOT_CHANGEABLE = 'priority_not_changeable';

    public const PRIORITY_CHANGE_TOO_SOON = 'priority_change_too_soon';

    public const PRIORITY_UNCHANGED = 'priority_unchanged';

    // Đổi thẻ cào
    public const TELCO_UNAVAILABLE = 'telco_unavailable';

    public const VALUE_INVALID = 'value_invalid';

    public const VALUE_UNAVAILABLE = 'value_unavailable';

    public const INVALID_CARD_FORMAT = 'invalid_card_format';

    public const CARD_ALREADY_SUBMITTED = 'card_already_submitted';

    public const TOO_MANY_PENDING = 'too_many_pending';

    public const CARD_EXCHANGE_LOCKED = 'card_exchange_locked';

    // Bảo hành
    public const WARRANTY_CLAIM_OPEN = 'warranty_claim_open';

    public const WARRANTY_EXPIRED = 'warranty_expired';

    public const WARRANTY_ITEM_INVALID = 'warranty_item_invalid';

    // Không tìm thấy (404)
    public const NOT_FOUND = 'not_found';

    public const PRODUCT_NOT_FOUND = 'product_not_found';

    public const PACKAGE_NOT_FOUND = 'package_not_found';

    public const ORDER_NOT_FOUND = 'order_not_found';

    public const ITEM_NOT_FOUND = 'item_not_found';

    public const CARD_NOT_FOUND = 'card_not_found';

    public const DEPOSIT_NOT_FOUND = 'deposit_not_found';

    public const EVENT_NOT_FOUND = 'event_not_found';

    // Hệ thống
    public const RATE_LIMITED = 'rate_limited';

    public const MAINTENANCE = 'maintenance';

    public const DEPOSIT_UNAVAILABLE = 'deposit_unavailable';

    public const METHOD_NOT_ALLOWED = 'method_not_allowed';

    public const SERVER_ERROR = 'server_error';

    /** Phản hồi lỗi không đúng dạng `{error: {code}}` (ví dụ trang lỗi của máy chủ trung gian). */
    public const HTTP_ERROR = 'http_error';

    private function __construct()
    {
    }
}
