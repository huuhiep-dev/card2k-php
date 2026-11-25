<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Phí đổi thẻ theo rank của bạn.
 *
 * Sinh từ resources/openapi.json (`CardExchangeRatesResponse.data`) bằng bin/generate-models.php.
 *
 * @property-read int $wrong_value_penalty % trừ khi thẻ sai mệnh giá
 * @property-read list<CardExchangeRate> $rates
 */
final class CardExchangeRates extends Model
{
    protected const NESTED = [
        'rates' => [CardExchangeRate::class],
    ];
}
