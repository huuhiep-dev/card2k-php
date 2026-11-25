<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Loại sự kiện webhook (`Event::$event`).
 *
 * Hằng số chuỗi, không dùng `enum` của PHP: shop có thể thêm giá trị mới, và một giá trị lạ
 * không được làm hỏng code của bạn.
 */
final class EventType
{
    /** Đơn đổi trạng thái; `data` là `Order`. */
    public const ORDER_UPDATED = 'order.updated';

    /** Thẻ có kết quả; `data` là `CardExchange`. */
    public const CARD_EXCHANGE_UPDATED = 'card_exchange.updated';

    /** Đã nhận tiền nạp; `data` là `Deposit`. */
    public const DEPOSIT_COMPLETED = 'deposit.completed';

    /** Webhook thử từ trang Kết nối API; `data` là mảng `{partner_id, message}`. */
    public const WEBHOOK_TEST = 'webhook.test';

    private function __construct()
    {
    }
}
