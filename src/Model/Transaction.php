<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một biến động số dư của tài khoản (cả trên web).
 *
 * Sinh từ resources/openapi.json (`Transaction`) bằng bin/generate-models.php.
 *
 * @property-read string $type purchase, refund, deposit, card_topup, card_exchange, wallet_transfer, withdrawal, withdrawal_refund, admin_credit, admin_debit, external_payment
 * @property-read string $wallet
 * @property-read string $wallet_label
 * @property-read int $amount Dương: cộng, âm: trừ
 * @property-read int $balance_after
 * @property-read string $description
 * @property-read bool $via_api
 * @property-read string $created_at
 */
final class Transaction extends Model
{
}
