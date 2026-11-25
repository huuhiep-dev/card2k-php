<?php

declare(strict_types=1);

namespace Card2K;

/**
 * Trạng thái yêu cầu bảo hành (`WarrantyClaim::$status`).
 *
 * Hằng số chuỗi, không dùng `enum` của PHP: shop có thể thêm giá trị mới, và một giá trị lạ
 * không được làm hỏng code của bạn.
 */
final class WarrantyClaimStatus
{
    /** Chờ shop xem. */
    public const PENDING = 'pending';

    /** Shop đang xử lý. */
    public const PROCESSING = 'processing';

    /** Đã xử lý xong. */
    public const RESOLVED = 'resolved';

    /** Bị từ chối (xem `reply`). */
    public const REJECTED = 'rejected';

    private function __construct()
    {
    }
}
