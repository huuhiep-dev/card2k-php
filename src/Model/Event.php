<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một sự kiện webhook. `data` là `Order`, `CardExchange` hoặc `Deposit` theo `event`,
 * còn `webhook.test` là mảng `{partner_id, message}`. Webhook nhận trực tiếp không có
 * `delivery`.
 *
 * Sinh từ resources/openapi.json (`Event`) bằng bin/generate-models.php.
 *
 * @property-read string $event_id
 * @property-read string $event
 * @property-read string|null $created_at
 * @property-read array{state: string, attempts: int, last_status: int|null, delivered_at: string|null}|null $delivery
 * @property-read Order|CardExchange|Deposit|array<string, mixed>|null $data Đơn, thẻ hoặc mã nạp như hiện tại, hoặc { partner_id, message } (webhook.test)
 */
final class Event extends Model
{
    /**
     * `data` thành `Order`, `CardExchange` hoặc `Deposit` theo `event`; loại lạ giữ nguyên mảng.
     *
     * @param  array<string, mixed>  $raw
     * @return class-string<Model>|null
     */
    protected function nestedType(string $key, array $raw): ?string
    {
        if ($key !== 'data') {
            return null;
        }

        return match ($raw['event'] ?? null) {
            'order.updated' => Order::class,
            'card_exchange.updated' => CardExchange::class,
            'deposit.completed' => Deposit::class,
            default => null,
        };
    }
}
