<?php

declare(strict_types=1);

namespace Card2K;

use ArrayIterator;
use Card2K\Model\Event;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Một lượt đọc sự kiện webhook (cũ nhất trước). Đọc tiếp bằng `after = lastEventId()`.
 *
 * @implements IteratorAggregate<int, Event>
 */
final class EventPage implements Countable, IteratorAggregate
{
    /**
     * @param  list<Event>  $data
     */
    public function __construct(
        public readonly array $data,
        public readonly bool $hasMore,
    ) {
    }

    /**
     * `event_id` cuối của lượt này: lưu lại, lần sau đọc tiếp từ đây. Null khi lượt rỗng.
     */
    public function lastEventId(): ?string
    {
        $last = $this->data[count($this->data) - 1] ?? null;

        return $last instanceof Event ? $last->event_id : null;
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return Traversable<int, Event>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }
}
