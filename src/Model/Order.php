<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một đơn đặt qua kết nối này.
 *
 * Sinh từ resources/openapi.json (`Order`) bằng bin/generate-models.php.
 *
 * @property-read string $code
 * @property-read string|null $reference Idempotency-Key lúc đặt
 * @property-read string $status
 * @property-read array{id: int, name: string, type: string}|null $product
 * @property-read array{id: int|null, name: string}|null $package
 * @property-read int $quantity
 * @property-read int|null $unit_price
 * @property-read int|null $list_price
 * @property-read int $total
 * @property-read array{code: string, discount: int}|null $voucher
 * @property-read list<array{id: int|null, label: string, value: string}> $fields
 * @property-read list<OrderItem> $items
 * @property-read list<Card> $cards
 * @property-read string|null $note
 * @property-read string|null $delivery
 * @property-read string $created_at
 * @property-read string $updated_at
 * @property-read string|null $completed_at
 */
final class Order extends Model
{
    protected const NESTED = [
        'items' => [OrderItem::class],
        'cards' => [Card::class],
    ];
}
