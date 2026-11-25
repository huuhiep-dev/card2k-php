<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Tài khoản của key: số dư hai ví, rank, quyền, giới hạn mỗi đơn.
 *
 * Sinh từ resources/openapi.json (`Account`) bằng bin/generate-models.php.
 *
 * @property-read int $balance Ví mua hàng
 * @property-read int $exchange_balance Ví đổi thẻ
 * @property-read string $currency
 * @property-read string|null $rank
 * @property-read string $exchange_rank Rank đổi thẻ
 * @property-read list<string> $scopes
 * @property-read array{max_cards_per_order: int, max_game_quantity: int} $limits
 */
final class Account extends Model
{
}
