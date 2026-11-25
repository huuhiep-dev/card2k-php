<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Trạng thái thẻ gửi đổi (`CardExchange::$status`).
 *
 * Hằng số chuỗi, không dùng `enum` của PHP: shop có thể thêm giá trị mới, và một giá trị lạ
 * không được làm hỏng code của bạn.
 */
final class CardExchangeStatus
{
    /** Đang chờ nhà mạng trả kết quả. */
    public const PENDING = 'pending';

    /** Thẻ đúng, đúng mệnh giá. */
    public const SUCCESS = 'success';

    /** Thẻ đúng nhưng sai mệnh giá (bị trừ phạt). */
    public const WRONG_VALUE = 'wrong_value';

    /** Thẻ sai hoặc đã dùng. */
    public const INVALID = 'invalid';

    /** Lỗi, không cộng tiền. */
    public const FAILED = 'failed';

    private function __construct()
    {
    }
}
