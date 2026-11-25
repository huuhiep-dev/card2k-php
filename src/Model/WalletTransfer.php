<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một lần chuyển Ví đổi thẻ sang Ví mua hàng, với số dư hai ví sau khi
 * chuyển.
 *
 * Sinh từ resources/openapi.json (`WalletTransferResponse.data`) bằng bin/generate-models.php.
 *
 * @property-read int $id
 * @property-read string $reference Idempotency-Key
 * @property-read int $amount
 * @property-read int $balance Ví mua hàng lúc này
 * @property-read int $exchange_balance Ví đổi thẻ lúc này
 * @property-read string|null $created_at
 */
final class WalletTransfer extends Model
{
}
