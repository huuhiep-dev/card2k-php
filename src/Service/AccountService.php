<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Model\Account;

/**
 * Tài khoản của key.
 */
final class AccountService extends Service
{
    /**
     * Số dư hai ví, rank, quyền của key, giới hạn mỗi đơn. `GET /me`
     */
    public function get(): Account
    {
        return Account::from($this->data($this->transport->send('GET', '/me')));
    }
}
