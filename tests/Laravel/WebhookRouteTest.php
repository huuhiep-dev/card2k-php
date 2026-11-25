<?php

declare(strict_types=1);

namespace Card2K\Tests\Laravel;

use Card2K\Laravel\Events\CardExchangeUpdated;
use Card2K\Laravel\Events\DepositCompleted;
use Card2K\Laravel\Events\OrderUpdated;
use Card2K\Laravel\Events\WebhookReceived;
use Card2K\Laravel\WebhookRoute;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RuntimeException;

final class WebhookRouteTest extends LaravelTestCase
{
    private const EVENT_ID = '9b2f6a4e-3c1d-4f8a-9e7b-1a2b3c4d5e6f';

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->group(function (): void {
            WebhookRoute::register('/card2k/webhook');
        });
        // The same through the macro the README shows.
        $router->macroCall('card2kWebhook', ['/card2k/qua-macro']);
    }

    /**
     * Gửi webhook ký giống hệt shop gửi.
     *
     * @param  array<string, mixed>  $data
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    private function sendWebhook(string $event, array $data, string $key = self::KEY, string $eventId = self::EVENT_ID): TestResponse
    {
        $body = (string) json_encode(['event' => $event, 'event_id' => $eventId, 'created_at' => '2026-11-26T10:00:00+07:00', 'data' => $data]);
        $timestamp = time();

        return $this->call('POST', '/card2k/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_SIGNATURE_V2' => 'sha256='.hash_hmac('sha256', "{$timestamp}.{$body}", $key),
        ], $body);
    }

    public function test_the_route_has_a_name_and_no_csrf_check(): void
    {
        $macroRoute = Route::getRoutes()->match(Request::create('/card2k/qua-macro', 'POST'));
        self::assertSame('card2k.webhook', $macroRoute->getName());

        $route = Route::getRoutes()->match(Request::create('/card2k/webhook', 'POST'));
        self::assertSame('card2k.webhook', $route->getName());
        self::assertSame(['POST'], array_values(array_diff($route->methods(), ['HEAD'])));
        self::assertNotEmpty(array_intersect([VerifyCsrfToken::class, ValidateCsrfToken::class], $route->excludedMiddleware()));
    }

    public function test_each_kind_of_event_is_dispatched_with_its_model(): void
    {
        Event::fake();

        $this->sendWebhook('order.updated', ['code' => 'DH1', 'reference' => 'DON-1', 'status' => 'completed'], eventId: 'e-order')->assertOk();
        $this->sendWebhook('card_exchange.updated', ['id' => '123', 'reference' => 'THE-1', 'status' => 'success', 'amount' => 8500], eventId: 'e-card')->assertOk();
        $this->sendWebhook('deposit.completed', ['code' => 'AB12', 'status' => 'completed', 'paid_amount' => 50000], eventId: 'e-deposit')->assertOk();
        $this->sendWebhook('webhook.test', ['partner_id' => 'P1', 'message' => 'Xin chào'], eventId: 'e-test')->assertOk();

        Event::assertDispatched(OrderUpdated::class, fn (OrderUpdated $e): bool => $e->order->reference === 'DON-1' && $e->event->event_id === 'e-order');
        Event::assertDispatched(CardExchangeUpdated::class, fn (CardExchangeUpdated $e): bool => $e->card->amount === 8500);
        Event::assertDispatched(DepositCompleted::class, fn (DepositCompleted $e): bool => $e->deposit->paid_amount === 50000);
        Event::assertDispatchedTimes(WebhookReceived::class, 4);
        Event::assertDispatched(WebhookReceived::class, fn (WebhookReceived $e): bool => $e->event->event === 'webhook.test' && $e->event->data === ['partner_id' => 'P1', 'message' => 'Xin chào']);
    }

    public function test_a_wrong_signature_is_refused_and_nothing_dispatched(): void
    {
        Event::fake([WebhookReceived::class, OrderUpdated::class]);

        $this->sendWebhook('order.updated', ['code' => 'DH1'], key: 'key-khac')->assertStatus(400);

        Event::assertNotDispatched(WebhookReceived::class);
        Event::assertNotDispatched(OrderUpdated::class);
    }

    public function test_an_event_sent_again_is_handled_once(): void
    {
        Event::fake();

        $this->sendWebhook('order.updated', ['code' => 'DH1'])->assertOk()->assertSee('OK');
        $this->sendWebhook('order.updated', ['code' => 'DH1'])->assertOk()->assertSee('Đã nhận trước đó.');

        Event::assertDispatchedTimes(OrderUpdated::class, 1);
    }

    public function test_a_failing_listener_leaves_the_event_to_be_sent_again(): void
    {
        $calls = 0;
        Event::listen(OrderUpdated::class, function () use (&$calls): void {
            $calls++;
            if ($calls === 1) {
                throw new RuntimeException('database down');
            }
        });

        $this->sendWebhook('order.updated', ['code' => 'DH1'])->assertStatus(500);
        self::assertFalse(Cache::has('card2k:webhook:'.self::EVENT_ID));

        $this->sendWebhook('order.updated', ['code' => 'DH1'])->assertOk()->assertSee('OK');
        self::assertSame(2, $calls);
        self::assertTrue(Cache::has('card2k:webhook:'.self::EVENT_ID));
    }
}
