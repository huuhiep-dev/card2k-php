<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Trạng thái mã nạp tiền (`Deposit::$status`).
 *
 * Hằng số chuỗi, không dùng `enum` của PHP: shop có thể thêm giá trị mới, và một giá trị lạ
 * không được làm hỏng code của bạn.
 */
final class DepositStatus
{
    /** Đang hiện, chờ chuyển khoản. */
    public const PENDING = 'pending';

    /** Hết hạn hiện (tiền tới trước `credit_until` vẫn được cộng). */
    public const EXPIRED = 'expired';

    /** Đã nhận tiền. */
    public const COMPLETED = 'completed';

    private function __construct()
    {
    }
}
