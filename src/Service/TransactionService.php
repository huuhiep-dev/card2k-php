<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Model\Transaction;
use Card2K\Page;
use Generator;

/**
 * Biến động số dư hai ví của tài khoản (cả giao dịch trên web).
 */
final class TransactionService extends Service
{
    /**
     * Một trang giao dịch, mới nhất trước. `GET /transactions`
     *
     * @param  array{wallet?: string, type?: string, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string, page?: int, per_page?: int}  $filters
     *   `wallet`: {@see \Card2K\Wallet}
     * @return Page<Transaction>
     */
    public function list(array $filters = []): Page
    {
        $query = self::pick($filters, ['wallet', 'type', 'from', 'to', 'page', 'per_page']);

        return $this->page($this->transport->send('GET', '/transactions', $query), Transaction::class);
    }

    /**
     * Mọi giao dịch khớp bộ lọc, tự sang trang.
     *
     * @param  array{wallet?: string, type?: string, from?: \DateTimeInterface|string, to?: \DateTimeInterface|string, page?: int, per_page?: int}  $filters
     * @return Generator<int, Transaction>
     */
    public function all(array $filters = []): Generator
    {
        return $this->eachPage($filters, fn (array $page): Page => $this->list($page));
    }
}
