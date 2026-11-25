<?php

declare(strict_types=1);

namespace Card2K\Laravel\Events;

use Card2K\Model\CardExchange;
use Card2K\Model\Event;

/**
 * Webhook `card_exchange.updated`: thẻ gửi đổi đã có kết quả. `$card->reference` là mã của bạn.
 */
final class CardExchangeUpdated
{
    public function __construct(
        public readonly CardExchange $card,
        public readonly Event $event,
    ) {
    }
}
