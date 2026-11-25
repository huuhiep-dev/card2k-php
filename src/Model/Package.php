<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một gói của sản phẩm; đặt đơn bằng `id` này.
 *
 * Sinh từ resources/openapi.json (`Package`) bằng bin/generate-models.php.
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read int $price Giá theo rank của bạn
 * @property-read int $list_price
 * @property-read int|null $face_value
 * @property-read int|null $so_amount
 * @property-read bool $in_stock
 */
final class Package extends Model
{
}
