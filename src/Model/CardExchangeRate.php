<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Phí và tiền nhận được của một nhà mạng, một mệnh giá.
 *
 * Sinh từ resources/openapi.json (`CardExchangeRatesResponse.data.rates[]`) bằng bin/generate-models.php.
 *
 * @property-read string $telco
 * @property-read int $value
 * @property-read float $fee_percent
 * @property-read int $receive_amount
 */
final class CardExchangeRate extends Model
{
}
