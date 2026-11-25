<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một sản phẩm đang bán, giá theo rank của bạn.
 *
 * Sinh từ resources/openapi.json (`Product`) bằng bin/generate-models.php.
 *
 * @property-read int $id
 * @property-read string $type
 * @property-read string $name
 * @property-read string $slug
 * @property-read string|null $image_url
 * @property-read string|null $card_type
 * @property-read string|null $purchase_notice
 * @property-read list<ProductField> $fields
 * @property-read list<Package> $packages
 * @property-read array{max_priority_percent: int, top_percent: int, waiting: int}|null $queue
 */
final class Product extends Model
{
    protected const NESTED = [
        'fields' => [ProductField::class],
        'packages' => [Package::class],
    ];
}
