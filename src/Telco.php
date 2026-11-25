<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Mã nhà mạng khi gửi đổi thẻ (`telco`). Xem nhà mạng đang nhận bằng `cardExchanges->rates()`.
 *
 * Hằng số chuỗi, không dùng `enum` của PHP: shop có thể thêm giá trị mới, và một giá trị lạ
 * không được làm hỏng code của bạn.
 */
final class Telco
{
    public const VIETTEL = 'VIETTEL';

    public const VINAPHONE = 'VINAPHONE';

    public const MOBIFONE = 'MOBIFONE';

    public const VIETNAMOBILE = 'VIETNAMOBILE';

    public const ZING = 'ZING';

    public const GARENA = 'GARENA';

    public const GATE = 'GATE';

    public const VCOIN = 'VCOIN';

    public const APPOTA = 'APPOTA';

    public const CAROT = 'CAROT';

    private function __construct()
    {
    }
}
