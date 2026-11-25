<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một thẻ đã gửi đổi. Không bao giờ có mã thẻ.
 *
 * Sinh từ resources/openapi.json (`CardExchange`) bằng bin/generate-models.php.
 *
 * @property-read string $id
 * @property-read string|null $reference
 * @property-read string $status
 * @property-read string $telco
 * @property-read string $serial
 * @property-read int $declared_value
 * @property-read int|null $value Mệnh giá thật (null khi chờ)
 * @property-read float $fee_percent
 * @property-read int|null $wrong_value_penalty
 * @property-read int|null $amount Tiền cộng Ví đổi thẻ (null khi chờ)
 * @property-read string|null $message
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 * @property-read string|null $completed_at
 */
final class CardExchange extends Model
{
}
