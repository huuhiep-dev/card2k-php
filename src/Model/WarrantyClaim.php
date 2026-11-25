<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một yêu cầu bảo hành.
 *
 * Sinh từ resources/openapi.json (`WarrantyClaim`) bằng bin/generate-models.php.
 *
 * @property-read int $id
 * @property-read string $order_code
 * @property-read string $status
 * @property-read list<array{id: int, name: string}> $items
 * @property-read string $zalo_phone
 * @property-read string|null $note
 * @property-read string|null $reply Lời nhắn của shop khi xử lý xong
 * @property-read string|null $created_at
 * @property-read string|null $closed_at
 */
final class WarrantyClaim extends Model
{
}
