<?php

declare(strict_types=1);

namespace Card2K\Tests\Unit;

use Card2K\Model\Event;
use Card2K\Model\Order;
use Card2K\Tests\Support\TestCase;

final class PaginationTest extends TestCase
{
    public function test_all_walks_every_page(): void
    {
        $this->http
            ->json(200, ['data' => [['code' => 'A'], ['code' => 'B']], 'meta' => ['page' => 1, 'per_page' => 2, 'total' => 3, 'last_page' => 2]])
            ->json(200, ['data' => [['code' => 'C']], 'meta' => ['page' => 2, 'per_page' => 2, 'total' => 3, 'last_page' => 2]]);

        $codes = array_map(fn (Order $order) => $order->code, iterator_to_array($this->client()->orders->all(['status' => 'completed', 'per_page' => 2]), false));

        self::assertSame(['A', 'B', 'C'], $codes);
        self::assertCount(2, $this->http->requests);
        self::assertStringEndsWith('?status=completed&page=2&per_page=2', (string) $this->http->last()->getUri());
    }

    public function test_a_page_counts_and_iterates(): void
    {
        $this->http->json(200, ['data' => [['code' => 'A']], 'meta' => ['page' => 1, 'per_page' => 20, 'total' => 1, 'last_page' => 1]]);

        $page = $this->client()->orders->list();

        self::assertCount(1, $page);
        self::assertFalse($page->hasMore());
        foreach ($page as $order) {
            self::assertSame('A', $order->code);
        }
    }

    public function test_events_continue_after_the_last_one_read(): void
    {
        $this->http
            ->json(200, ['data' => [['event_id' => 'e1', 'event' => 'webhook.test', 'data' => ['partner_id' => 'P', 'message' => 'm']], ['event_id' => 'e2', 'event' => 'order.updated', 'data' => ['code' => 'DH1']]], 'meta' => ['has_more' => true]])
            ->json(200, ['data' => [['event_id' => 'e3', 'event' => 'card_exchange.updated', 'data' => ['id' => '9']]], 'meta' => ['has_more' => false]]);

        $events = iterator_to_array($this->client()->events->all('e0'), false);

        self::assertSame(['e1', 'e2', 'e3'], array_map(fn (Event $event) => $event->event_id, $events));
        self::assertSame(['partner_id' => 'P', 'message' => 'm'], $events[0]->data);
        self::assertInstanceOf(Order::class, $events[1]->data);
        self::assertStringEndsWith('/events?after=e2', (string) $this->http->last()->getUri());
    }

    public function test_an_empty_event_page_has_no_last_id(): void
    {
        $this->http->json(200, ['data' => [], 'meta' => ['has_more' => false]]);

        $page = $this->client()->events->list();

        self::assertNull($page->lastEventId());
        self::assertFalse($page->hasMore);
    }
}
