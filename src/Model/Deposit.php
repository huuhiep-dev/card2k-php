<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một mã nạp tiền: chuyển khoản đúng `transfer_content` tới một trong `accounts`
 * để nạp Ví mua hàng.
 *
 * Sinh từ resources/openapi.json (`Deposit`) bằng bin/generate-models.php.
 *
 * @property-read string $code
 * @property-read string $transfer_content Nội dung chuyển khoản, giữ nguyên
 * @property-read string $status expired: không còn hiện, tiền tới trước credit_until vẫn được cộng
 * @property-read int|null $paid_amount
 * @property-read int $min_amount
 * @property-read int $max_amount
 * @property-read string $expires_at
 * @property-read string|null $credit_until
 * @property-read string|null $created_at
 * @property-read string|null $completed_at
 * @property-read list<DepositAccount> $accounts
 */
final class Deposit extends Model
{
    protected const NESTED = [
        'accounts' => [DepositAccount::class],
    ];
}
