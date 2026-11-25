<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Ví của tài khoản (`Transaction::$wallet`).
 *
 * Hằng số chuỗi, không dùng `enum` của PHP: shop có thể thêm giá trị mới, và một giá trị lạ
 * không được làm hỏng code của bạn.
 */
final class Wallet
{
    /** Ví mua hàng. */
    public const MAIN = 'main';

    /** Ví đổi thẻ. */
    public const EXCHANGE = 'exchange';

    private function __construct()
    {
    }
}
