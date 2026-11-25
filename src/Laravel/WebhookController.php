<?php

declare(strict_types=1);

namespace Card2K\Laravel;

use Card2K\Laravel\Events\CardExchangeUpdated;
use Card2K\Laravel\Events\DepositCompleted;
use Card2K\Laravel\Events\OrderUpdated;
use Card2K\Laravel\Events\WebhookReceived;
use Card2K\Model\CardExchange;
use Card2K\Model\Deposit;
use Card2K\Model\Order;
use Card2K\Webhook\SignatureVerificationException;
use Card2K\Webhook\Webhook;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * `Route::card2kWebhook()`: kiểm chữ ký, bỏ sự kiện đã xử lý (theo `event_id`), rồi phát
 * `WebhookReceived` và event theo loại (`OrderUpdated`, `CardExchangeUpdated`, `DepositCompleted`).
 *
 * `event_id` chỉ được nhớ sau khi các listener chạy xong: listener lỗi thì shop nhận HTTP 500 và
 * gửi lại sau, không bị mất sự kiện. Việc nặng nên để listener `ShouldQueue`.
 */
final class WebhookController
{
    public function __invoke(Request $request, Config $config, Cache $cache, Dispatcher $events): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->headers->all(),
                (string) $config->get('card2k.api_key'),
                (int) $config->get('card2k.webhook.tolerance', Webhook::DEFAULT_TOLERANCE),
            );
        } catch (SignatureVerificationException $exception) {
            return new Response($exception->getMessage(), 400);
        }

        $seenKey = 'card2k:webhook:'.$event->event_id;
        if ($cache->has($seenKey)) {
            return new Response('Đã nhận trước đó.', 200);
        }

        $events->dispatch(new WebhookReceived($event));

        $data = $event->data;
        if ($data instanceof Order) {
            $events->dispatch(new OrderUpdated($data, $event));
        } elseif ($data instanceof CardExchange) {
            $events->dispatch(new CardExchangeUpdated($data, $event));
        } elseif ($data instanceof Deposit) {
            $events->dispatch(new DepositCompleted($data, $event));
        }

        $cache->put($seenKey, true, now()->addHours((int) $config->get('card2k.webhook.remember_hours', 72)));

        return new Response('OK', 200);
    }
}
