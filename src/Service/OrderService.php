<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Model\Order;
use Card2K\Model\QueuePriorityResult;
use Card2K\Page;
use Generator;

/**
 * Đặt đơn (trừ Ví mua hàng), tra đơn, đổi phí ưu tiên Sò hàng chờ.
 */
final class OrderService extends Service
{
    /**
     * Đặt một đơn. `POST /orders` (quyền `orders`)
     *
     * Truyền `$idempotencyKey` là mã đơn bên bạn (1–64 ký tự `A-Z a-z 0-9 _ - . :`): gửi lại cùng
     * mã với cùng nội dung chỉ trả lại đơn cũ, không trừ tiền lần nữa. Bỏ trống thì SDK tự tạo
     * (lấy lại bằng `$order->reference`, hoặc `NetworkException::getIdempotencyKey()` khi lỗi mạng).
     *
     * @param  array{package_id: int, quantity?: int, fields?: array<int|string, string>, voucher_code?: string, queue?: array{account?: string, priority_percent?: int, top?: bool}}  $params
     *   `fields`: `[id ô => giá trị]` (xem `Product::$fields`)
     *
     * @throws \Card2K\Exception\InsufficientBalanceException
     * @throws \Card2K\Exception\ValidationException
     */
    public function create(array $params, ?string $idempotencyKey = null): Order
    {
        if (isset($params['fields'])) {
            $params['fields'] = (object) $params['fields'];
        }

        $response = $this->transport->send('POST', '/orders', body: $params, headers: [
            'Idempotency-Key' => $idempotencyKey ?? self::newKey(),
        ]);

        return Order::from($this->data($response));
    }

    /**
     * Một đơn đặt qua kết nối này. `GET /orders/{code}`
     *
     * @throws \Card2K\Exception\NotFoundException
     */
    public function get(string $code): Order
    {
        return Order::from($this->data($this->transport->send('GET', '/orders/'.self::segment($code))));
    }

    /**
     * Một trang đơn, mới nhất trước (có `updated_since` thì thay đổi cũ nhất trước). `GET /orders`
     *
     * @param  array{status?: string, reference?: string, updated_since?: \DateTimeInterface|string, page?: int, per_page?: int}  $filters
     * @return Page<Order>
     */
    public function list(array $filters = []): Page
    {
        $query = self::pick($filters, ['status', 'reference', 'updated_since', 'page', 'per_page']);

        return $this->page($this->transport->send('GET', '/orders', $query), Order::class);
    }

    /**
     * Mọi đơn khớp bộ lọc, tự sang trang.
     *
     * @param  array{status?: string, reference?: string, updated_since?: \DateTimeInterface|string, page?: int, per_page?: int}  $filters
     * @return Generator<int, Order>
     */
    public function all(array $filters = []): Generator
    {
        return $this->eachPage($filters, fn (array $page): Page => $this->list($page));
    }

    /**
     * Đổi phí ưu tiên của một dòng Sò Garena hàng chờ đang chờ. `PATCH /orders/{code}/items/{item}/queue-priority`
     *
     * Tiền chênh lệch trừ thêm hoặc hoàn lại vào ví đã trả đơn (`difference`).
     *
     * @param  int  $itemId  `OrderItem::$id`
     * @param  array{priority_percent?: int, top?: bool}  $params
     */
    public function changeQueuePriority(string $code, int $itemId, array $params): QueuePriorityResult
    {
        $path = '/orders/'.self::segment($code).'/items/'.self::segment($itemId).'/queue-priority';

        return QueuePriorityResult::from($this->data($this->transport->send('PATCH', $path, body: $params)));
    }
}
