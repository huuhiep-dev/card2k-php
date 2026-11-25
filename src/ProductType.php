<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Loại sản phẩm (`Product::$type`).
 *
 * Hằng số chuỗi, không dùng `enum` của PHP: shop có thể thêm giá trị mới, và một giá trị lạ
 * không được làm hỏng code của bạn.
 */
final class ProductType
{
    /** Nạp game và sản phẩm thường. */
    public const GAME = 'game';

    /** Thẻ cào. */
    public const CARD = 'card';

    /** Sò Garena hàng chờ. */
    public const GARENA_QUEUE = 'garena_queue';

    private function __construct()
    {
    }
}
