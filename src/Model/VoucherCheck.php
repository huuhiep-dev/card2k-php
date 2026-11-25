<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Mã giảm giá giảm bao nhiêu cho một gói (chưa đặt đơn).
 *
 * Sinh từ resources/openapi.json (`VoucherCheckResponse.data`) bằng bin/generate-models.php.
 *
 * @property-read string $code
 * @property-read string|null $name
 * @property-read int $subtotal
 * @property-read int $discount
 * @property-read int $total
 */
final class VoucherCheck extends Model
{
}
