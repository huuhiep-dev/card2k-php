<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một dòng của đơn; `id` dùng để bảo hành và đổi phí ưu tiên Sò.
 *
 * Sinh từ resources/openapi.json (`OrderItem`) bằng bin/generate-models.php.
 *
 * @property-read int $id
 * @property-read string $status
 * @property-read string|null $note
 * @property-read list<Card> $cards
 * @property-read array{account: string, so_target: int, so_received: int, priority_percent: int, priority_fee: int, status: string, message: string|null, position: int|null, refunded: int}|null $queue
 * @property-read array{days: int, ends_at: string|null, lifetime: bool, active: bool}|null $warranty
 */
final class OrderItem extends Model
{
    protected const NESTED = [
        'cards' => [Card::class],
    ];
}
