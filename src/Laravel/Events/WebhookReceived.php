<?php

declare(strict_types=1);

namespace Card2K\Laravel\Events;

use Card2K\Model\Event;

/**
 * Mọi webhook đúng chữ ký (mỗi `event_id` một lần), kể cả `webhook.test` và loại shop thêm sau.
 */
final class WebhookReceived
{
    public function __construct(public readonly Event $event)
    {
    }
}
