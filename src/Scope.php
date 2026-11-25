<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Quyền của key (`Account::$scopes`). Bật / tắt ở Tài khoản → Kết nối API.
 *
 * Hằng số chuỗi, không dùng `enum` của PHP: shop có thể thêm giá trị mới, và một giá trị lạ
 * không được làm hỏng code của bạn.
 */
final class Scope
{
    /** Đặt đơn, đổi phí ưu tiên Sò, kiểm tra mã giảm giá. */
    public const ORDERS = 'orders';

    /** Gửi thẻ cào để đổi. */
    public const CARD_EXCHANGE = 'card_exchange';

    /** Gửi yêu cầu bảo hành. */
    public const WARRANTY = 'warranty';

    /** Lấy thông tin chuyển khoản nạp Ví mua hàng. */
    public const DEPOSIT = 'deposit';

    /** Chuyển Ví đổi thẻ sang Ví mua hàng. */
    public const WALLET_TRANSFER = 'wallet_transfer';

    private function __construct()
    {
    }
}
