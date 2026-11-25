<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Model\VoucherCheck;

/**
 * Mã giảm giá.
 */
final class VoucherService extends Service
{
    /**
     * Xem mã giảm bao nhiêu cho một gói, trước khi đặt đơn. `POST /vouchers/check` (quyền `orders`)
     *
     * @param  array{package_id: int, quantity?: int, voucher_code: string}  $params
     *
     * @throws \Card2K\Exception\ValidationException `voucher_refused`
     */
    public function check(array $params): VoucherCheck
    {
        return VoucherCheck::from($this->data($this->transport->send('POST', '/vouchers/check', body: $params)));
    }
}
