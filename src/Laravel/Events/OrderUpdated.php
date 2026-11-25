<?php

declare(strict_types=1);

namespace Card2K\Laravel\Events;

use Card2K\Model\Event;
use Card2K\Model\Order;

/**
 * Webhook `order.updated`: đơn đổi trạng thái. `$order->reference` là mã đơn bên bạn.
 */
final class OrderUpdated
{
    public function __construct(
        public readonly Order $order,
        public readonly Event $event,
    ) {
    }
}
