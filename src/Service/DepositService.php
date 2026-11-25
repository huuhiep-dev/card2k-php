<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Model\Deposit;

/**
 * Nạp Ví mua hàng bằng chuyển khoản.
 */
final class DepositService extends Service
{
    /**
     * Lấy thông tin chuyển khoản. `POST /deposits` (quyền `deposit`)
     *
     * Mã đang hiện (30 phút) được dùng lại, nên gọi nhiều lần vẫn ra cùng một mã. Tiền tới thì
     * shop gửi webhook `deposit.completed`.
     *
     * @throws \Card2K\Exception\ServiceUnavailableException `deposit_unavailable`
     */
    public function create(): Deposit
    {
        return Deposit::from($this->data($this->transport->send('POST', '/deposits')));
    }

    /**
     * Một mã nạp kết nối này đã lấy. `GET /deposits/{code}`
     */
    public function get(string $code): Deposit
    {
        return Deposit::from($this->data($this->transport->send('GET', '/deposits/'.self::segment($code))));
    }
}
