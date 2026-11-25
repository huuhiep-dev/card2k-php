<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Model\WarrantyClaim;
use Card2K\Page;
use Generator;

/**
 * Yêu cầu bảo hành cho các đơn đặt qua kết nối này.
 */
final class WarrantyClaimService extends Service
{
    /**
     * Gửi yêu cầu bảo hành cho các dòng còn hạn của một đơn. `POST /orders/{code}/warranty-claims` (quyền `warranty`)
     *
     * @param  array{item_ids: list<int>, zalo_phone: string, note?: string}  $params  `item_ids`: `OrderItem::$id`
     *
     * @throws \Card2K\Exception\ConflictException `warranty_claim_open`: đơn đã có yêu cầu đang mở (`claim_id`)
     * @throws \Card2K\Exception\ValidationException `warranty_expired`, `warranty_item_invalid`, …
     */
    public function create(string $orderCode, array $params): WarrantyClaim
    {
        $path = '/orders/'.self::segment($orderCode).'/warranty-claims';

        return WarrantyClaim::from($this->data($this->transport->send('POST', $path, body: $params)));
    }

    /**
     * Một trang yêu cầu bảo hành, mới nhất trước. `GET /warranty-claims`
     *
     * @param  array{order_code?: string, status?: string, page?: int, per_page?: int}  $filters
     * @return Page<WarrantyClaim>
     */
    public function list(array $filters = []): Page
    {
        $query = self::pick($filters, ['order_code', 'status', 'page', 'per_page']);

        return $this->page($this->transport->send('GET', '/warranty-claims', $query), WarrantyClaim::class);
    }

    /**
     * Mọi yêu cầu bảo hành khớp bộ lọc, tự sang trang.
     *
     * @param  array{order_code?: string, status?: string, page?: int, per_page?: int}  $filters
     * @return Generator<int, WarrantyClaim>
     */
    public function all(array $filters = []): Generator
    {
        return $this->eachPage($filters, fn (array $page): Page => $this->list($page));
    }
}
