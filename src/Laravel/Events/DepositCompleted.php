<?php

declare(strict_types=1);

namespace Card2K\Laravel\Events;

use Card2K\Model\Deposit;
use Card2K\Model\Event;

/**
 * Webhook `deposit.completed`: tiền nạp đã tới, Ví mua hàng đã được cộng.
 */
final class DepositCompleted
{
    public function __construct(
        public readonly Deposit $deposit,
        public readonly Event $event,
    ) {
    }
}
