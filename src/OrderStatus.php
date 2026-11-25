<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Trạng thái đơn (`Order::$status`, `OrderItem::$status`).
 *
 * Hằng số chuỗi, không dùng `enum` của PHP: shop có thể thêm giá trị mới, và một giá trị lạ
 * không được làm hỏng code của bạn.
 */
final class OrderStatus
{
    /** Chờ chuyển khoản (đơn qua API không có trạng thái này). */
    public const AWAITING_PAYMENT = 'awaiting_payment';

    /** Đang chờ xử lý. */
    public const PENDING = 'pending';

    /** Đang xử lý. */
    public const PROCESSING = 'processing';

    /** Xong. */
    public const COMPLETED = 'completed';

    /** Xong một phần, phần còn lại đã hoàn tiền. */
    public const PARTIAL = 'partial';

    /** Đã hoàn tiền. */
    public const REFUNDED = 'refunded';

    /** Đã hủy. */
    public const CANCELLED = 'cancelled';

    private function __construct()
    {
    }
}
