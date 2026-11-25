<?php

declare(strict_types=1);

namespace Card2K;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Một trang của danh sách (đơn, thẻ, giao dịch, bảo hành). Duyệt trực tiếp bằng `foreach`.
 *
 * Muốn lấy mọi trang thì dùng `all()` của service thay vì tự tăng `page`.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
final class Page implements Countable, IteratorAggregate
{
    /**
     * @param  list<T>  $data
     */
    public function __construct(
        public readonly array $data,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
        public readonly int $lastPage,
    ) {
    }

    public function hasMore(): bool
    {
        return $this->page < $this->lastPage;
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }
}
