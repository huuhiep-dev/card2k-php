<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Model\WalletTransfer;

/**
 * Chuyển tiền giữa hai ví của tài khoản (tiền không ra khỏi shop).
 */
final class WalletService extends Service
{
    /**
     * Chuyển tiền từ Ví đổi thẻ sang Ví mua hàng (một chiều). `POST /wallet/transfers` (quyền `wallet_transfer`)
     *
     * Truyền `$idempotencyKey` là mã của bạn cho lần chuyển này: gửi lại cùng mã chỉ chuyển một
     * lần. Bỏ trống thì SDK tự tạo (lấy lại bằng `$transfer->reference`).
     *
     * @param  int  $amount  VND, tối thiểu 1.000
     *
     * @throws \Card2K\Exception\InsufficientBalanceException
     */
    public function transfer(int $amount, ?string $idempotencyKey = null): WalletTransfer
    {
        $response = $this->transport->send('POST', '/wallet/transfers', body: ['amount' => $amount], headers: [
            'Idempotency-Key' => $idempotencyKey ?? self::newKey(),
        ]);

        return WalletTransfer::from($this->data($response));
    }
}
