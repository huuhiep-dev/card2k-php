<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một tài khoản ngân hàng nhận tiền nạp.
 *
 * Sinh từ resources/openapi.json (`Deposit.accounts[]`) bằng bin/generate-models.php.
 *
 * @property-read string $bank
 * @property-read string $bank_label
 * @property-read string $bin Mã BIN để dựng VietQR
 * @property-read string $account_number
 * @property-read string $account_name
 * @property-read string|null $label
 * @property-read string $logo_url
 */
final class DepositAccount extends Model
{
}
