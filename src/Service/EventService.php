<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\EventPage;
use Card2K\Model\Event;
use Generator;

/**
 * Sự kiện webhook 30 ngày gần nhất, để lấy lại những lần webhook không tới.
 */
final class EventService extends Service
{
    /**
     * Một lượt sự kiện, cũ nhất trước. `GET /events`
     *
     * @param  string|null  $after  `event_id` cuối đã đọc (lượt trước: `EventPage::lastEventId()`)
     * @param  int|null  $limit  1–100, mặc định 50
     *
     * @throws \Card2K\Exception\NotFoundException `event_not_found`: `after` không có hoặc quá 30 ngày
     */
    public function list(?string $after = null, ?int $limit = null): EventPage
    {
        $response = $this->transport->send('GET', '/events', ['after' => $after, 'limit' => $limit]);
        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];

        return new EventPage(Event::listFrom($this->rows($response)), ($meta['has_more'] ?? false) === true);
    }

    /**
     * Mọi sự kiện sau `$after`, tự đọc tiếp.
     *
     * @return Generator<int, Event>
     */
    public function all(?string $after = null, ?int $limit = null): Generator
    {
        do {
            $page = $this->list($after, $limit);
            foreach ($page->data as $event) {
                yield $event;
            }
            $after = $page->lastEventId();
        } while ($page->hasMore && $after !== null);
    }
}
