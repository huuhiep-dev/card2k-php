<?php

declare(strict_types=1);

namespace Card2K\Tests\Unit;

use Card2K\Client;
use Card2K\EventPage;
use Card2K\Model\Account;
use Card2K\Model\CardExchange;
use Card2K\Model\CardExchangeRate;
use Card2K\Model\CardExchangeRates;
use Card2K\Model\Deposit;
use Card2K\Model\DepositAccount;
use Card2K\Model\Event;
use Card2K\Model\Order;
use Card2K\Model\OrderItem;
use Card2K\Model\Package;
use Card2K\Model\Product;
use Card2K\Model\ProductField;
use Card2K\Model\QueuePriorityResult;
use Card2K\Model\Transaction;
use Card2K\Model\VoucherCheck;
use Card2K\Model\WalletTransfer;
use Card2K\Model\WarrantyClaim;
use Card2K\Page;
use Card2K\Tests\Support\Spec;
use Card2K\Tests\Support\TestCase;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Mỗi endpoint của spec: SDK gọi đúng method, đường dẫn, tham số, body, header, và đọc được
 * phản hồi mẫu dựng từ schema.
 */
final class ServicesTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: callable(Client): mixed, 2: string, 3: array<mixed>|null, 4: array<string, string>, 5: class-string}>
     */
    public static function endpoints(): array
    {
        $since = new DateTimeImmutable('2026-11-20 08:00:00', new DateTimeZone('+07:00'));

        return [
            'GET /me' => ['/me', fn (Client $c) => $c->account->get(), '/api/v3/me', null, [], Account::class],
            'GET /products' => ['/products', fn (Client $c) => $c->products->list('card'), '/api/v3/products?type=card', null, [], Product::class],
            'GET /products/{id}' => ['/products/{id}', fn (Client $c) => $c->products->get(7), '/api/v3/products/7', null, [], Product::class],
            'GET /orders' => ['/orders', fn (Client $c) => $c->orders->list(['status' => 'completed', 'updated_since' => $since, 'per_page' => 50]), '/api/v3/orders?status=completed&updated_since=2026-11-20T01%3A00%3A00Z&per_page=50', null, [], Order::class],
            'POST /orders' => ['/orders', fn (Client $c) => $c->orders->create(['package_id' => 12, 'quantity' => 2, 'fields' => [3 => '123456789'], 'voucher_code' => 'GIAM10'], 'DON-1001'), '/api/v3/orders', ['package_id' => 12, 'quantity' => 2, 'fields' => ['3' => '123456789'], 'voucher_code' => 'GIAM10'], ['Idempotency-Key' => 'DON-1001'], Order::class],
            'GET /orders/{code}' => ['/orders/{code}', fn (Client $c) => $c->orders->get('DH 1/2'), '/api/v3/orders/DH%201%2F2', null, [], Order::class],
            'GET /events' => ['/events', fn (Client $c) => $c->events->list('9b2f6a4e-3c1d-4f8a-9e7b-1a2b3c4d5e6f', 10), '/api/v3/events?after=9b2f6a4e-3c1d-4f8a-9e7b-1a2b3c4d5e6f&limit=10', null, [], EventPage::class],
            'GET /transactions' => ['/transactions', fn (Client $c) => $c->transactions->list(['wallet' => 'exchange', 'from' => '2026-11-01T00:00:00+07:00']), '/api/v3/transactions?wallet=exchange&from=2026-11-01T00%3A00%3A00%2B07%3A00', null, [], Transaction::class],
            'GET /card-exchange/rates' => ['/card-exchange/rates', fn (Client $c) => $c->cardExchanges->rates(), '/api/v3/card-exchange/rates', null, [], CardExchangeRates::class],
            'GET /card-exchanges' => ['/card-exchanges', fn (Client $c) => $c->cardExchanges->list(['status' => 'pending', 'telco' => 'VIETTEL']), '/api/v3/card-exchanges?status=pending&telco=VIETTEL', null, [], CardExchange::class],
            'POST /card-exchanges' => ['/card-exchanges', fn (Client $c) => $c->cardExchanges->create(['telco' => 'VIETTEL', 'value' => 50000, 'serial' => '10000000001', 'code' => '200000000001', 'reference' => 'THE-1']), '/api/v3/card-exchanges', ['telco' => 'VIETTEL', 'value' => 50000, 'serial' => '10000000001', 'code' => '200000000001', 'reference' => 'THE-1'], [], CardExchange::class],
            'GET /card-exchanges/{id}' => ['/card-exchanges/{id}', fn (Client $c) => $c->cardExchanges->get('123456789012'), '/api/v3/card-exchanges/123456789012', null, [], CardExchange::class],
            'POST /orders/{code}/warranty-claims' => ['/orders/{code}/warranty-claims', fn (Client $c) => $c->warrantyClaims->create('DH1', ['item_ids' => [5], 'zalo_phone' => '0900000000', 'note' => 'Mã lỗi']), '/api/v3/orders/DH1/warranty-claims', ['item_ids' => [5], 'zalo_phone' => '0900000000', 'note' => 'Mã lỗi'], [], WarrantyClaim::class],
            'GET /warranty-claims' => ['/warranty-claims', fn (Client $c) => $c->warrantyClaims->list(['order_code' => 'DH1']), '/api/v3/warranty-claims?order_code=DH1', null, [], WarrantyClaim::class],
            'PATCH /orders/{code}/items/{item}/queue-priority' => ['/orders/{code}/items/{item}/queue-priority', fn (Client $c) => $c->orders->changeQueuePriority('DH1', 9, ['priority_percent' => 5]), '/api/v3/orders/DH1/items/9/queue-priority', ['priority_percent' => 5], [], QueuePriorityResult::class],
            'POST /vouchers/check' => ['/vouchers/check', fn (Client $c) => $c->vouchers->check(['package_id' => 12, 'voucher_code' => 'GIAM10']), '/api/v3/vouchers/check', ['package_id' => 12, 'voucher_code' => 'GIAM10'], [], VoucherCheck::class],
            'POST /deposits' => ['/deposits', fn (Client $c) => $c->deposits->create(), '/api/v3/deposits', null, [], Deposit::class],
            'GET /deposits/{code}' => ['/deposits/{code}', fn (Client $c) => $c->deposits->get('AB12CD'), '/api/v3/deposits/AB12CD', null, [], Deposit::class],
            'POST /wallet/transfers' => ['/wallet/transfers', fn (Client $c) => $c->wallet->transfer(60000, 'CHUYEN-1'), '/api/v3/wallet/transfers', ['amount' => 60000], ['Idempotency-Key' => 'CHUYEN-1'], WalletTransfer::class],
        ];
    }

    /**
     * @param  callable(Client): mixed  $call
     * @param  array<mixed>|null  $body
     * @param  array<string, string>  $headers
     * @param  class-string  $class
     */
    #[DataProvider('endpoints')]
    public function test_each_endpoint_is_called_and_read_correctly(string $specPath, callable $call, string $url, ?array $body, array $headers, string $class): void
    {
        $operation = (string) $this->dataName();
        $method = explode(' ', $operation)[0];
        $spec = Spec::load();
        $sample = $spec->sample($spec->responseSchema($method, $specPath));
        $this->http->json($method === 'POST' ? 201 : 200, is_array($sample) ? $sample : []);

        $result = $call($this->client());

        $request = $this->http->last();
        self::assertSame($method, $request->getMethod());
        self::assertSame('https://card2k.net'.$url, (string) $request->getUri());
        self::assertSame('Bearer '.self::KEY, $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        foreach ($headers as $name => $value) {
            self::assertSame($value, $request->getHeaderLine($name));
        }
        if ($body === null) {
            self::assertSame('', (string) $request->getBody());
        } else {
            self::assertSame($body, $this->http->lastBody());
            self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        }

        $first = match (true) {
            $result instanceof Page, $result instanceof EventPage => $result->data[0] ?? null,
            is_array($result) => $result[0] ?? null,
            default => $result,
        };
        self::assertInstanceOf($class === EventPage::class ? Event::class : $class, $first);
        if ($result instanceof Page) {
            self::assertSame(1, $result->total);
        }
    }

    public function test_every_endpoint_of_the_spec_has_a_method_and_a_test(): void
    {
        $covered = array_keys(self::endpoints());
        $operations = Spec::load()->operations();
        sort($covered);
        sort($operations);

        self::assertSame($operations, $covered, 'Spec có endpoint SDK chưa có (hoặc ngược lại): thêm method vào service và một dòng vào endpoints().');
    }

    public function test_nested_objects_become_models(): void
    {
        $spec = Spec::load();
        $this->http
            ->json(200, (array) $spec->sample($spec->responseSchema('GET', '/products/{id}')))
            ->json(200, (array) $spec->sample($spec->responseSchema('GET', '/orders/{code}')))
            ->json(200, (array) $spec->sample($spec->responseSchema('GET', '/card-exchange/rates')))
            ->json(201, (array) $spec->sample($spec->responseSchema('POST', '/deposits')))
            ->json(200, (array) $spec->sample($spec->responseSchema('PATCH', '/orders/{code}/items/{item}/queue-priority')));
        $client = $this->client();

        $product = $client->products->get(1);
        self::assertInstanceOf(ProductField::class, $product->fields[0]);
        self::assertInstanceOf(Package::class, $product->packages[0]);

        $order = $client->orders->get('DH1');
        self::assertInstanceOf(OrderItem::class, $order->items[0]);
        self::assertSame('x', $order->items[0]->cards[0]->code);
        self::assertSame(['code' => 'x', 'discount' => 1], $order->voucher);

        self::assertInstanceOf(CardExchangeRate::class, $client->cardExchanges->rates()->rates[0]);
        self::assertInstanceOf(DepositAccount::class, $client->deposits->create()->accounts[0]);
        self::assertInstanceOf(Order::class, $client->orders->changeQueuePriority('DH1', 1, ['top' => true])->order);
    }

    public function test_an_empty_fields_list_is_sent_as_a_json_object(): void
    {
        $this->http->json(201, ['data' => ['code' => 'DH1']]);

        $this->client()->orders->create(['package_id' => 1, 'fields' => []], 'K1');

        self::assertStringContainsString('"fields":{}', (string) $this->http->last()->getBody());
    }

    public function test_keys_are_made_when_not_given_and_are_valid(): void
    {
        $this->http->json(201, ['data' => []])->json(201, ['data' => []])->json(201, ['data' => []]);
        $client = $this->client();

        $client->orders->create(['package_id' => 1]);
        $orderKey = $this->http->last()->getHeaderLine('Idempotency-Key');
        $client->wallet->transfer(5000);
        $walletKey = $this->http->last()->getHeaderLine('Idempotency-Key');
        $client->cardExchanges->create(['telco' => 'VIETTEL', 'value' => 10000, 'serial' => 's', 'code' => 'c']);
        $reference = $this->http->lastBody()['reference'] ?? null;

        foreach ([$orderKey, $walletKey, $reference] as $key) {
            self::assertIsString($key);
            self::assertMatchesRegularExpression('/^[A-Za-z0-9_\-.:]{1,64}$/', $key);
        }
        self::assertNotSame($orderKey, $walletKey);
    }

    public function test_dates_are_sent_in_utc_and_strings_as_given(): void
    {
        $this->http->json(200, ['data' => [], 'meta' => []])->json(200, ['data' => [], 'meta' => []]);
        $client = $this->client();

        $client->transactions->list([
            'from' => new \DateTime('2026-11-25 07:30:00', new DateTimeZone('Asia/Ho_Chi_Minh')),
            'to' => new DateTimeImmutable('2026-11-25 23:59:59', new DateTimeZone('UTC')),
        ]);
        self::assertStringEndsWith('?from=2026-11-25T00%3A30%3A00Z&to=2026-11-25T23%3A59%3A59Z', (string) $this->http->last()->getUri());

        $client->cardExchanges->list(['updated_since' => '2026-11-25T10:00:00+07:00']);
        self::assertStringEndsWith('?updated_since=2026-11-25T10%3A00%3A00%2B07%3A00', (string) $this->http->last()->getUri());
    }

    public function test_find_card_by_reference(): void
    {
        $this->http
            ->json(200, ['data' => [['id' => '1', 'reference' => 'THE-1']], 'meta' => ['page' => 1, 'per_page' => 1, 'total' => 1, 'last_page' => 1]])
            ->json(200, ['data' => [], 'meta' => ['page' => 1, 'per_page' => 1, 'total' => 0, 'last_page' => 1]]);
        $client = $this->client();

        self::assertSame('1', $client->cardExchanges->findByReference('THE-1')?->id);
        self::assertStringEndsWith('/card-exchanges?reference=THE-1&per_page=1', (string) $this->http->last()->getUri());
        self::assertNull($client->cardExchanges->findByReference('KHONG-CO'));
    }

    public function test_unknown_fields_stay_readable(): void
    {
        $this->http->json(200, ['data' => ['code' => 'DH1', 'field_moi' => ['a' => 1]]]);

        $order = $this->client()->orders->get('DH1');

        self::assertSame(['a' => 1], $order->get('field_moi'));
        self::assertSame(['a' => 1], $order['field_moi']);
        self::assertNull($order->get('khong_co'));
        self::assertSame('mac-dinh', $order->get('khong_co', 'mac-dinh'));
        self::assertNull($order['khong_co']);
        self::assertSame(['code' => 'DH1', 'field_moi' => ['a' => 1]], $order->toArray());
        self::assertSame('{"code":"DH1","field_moi":{"a":1}}', json_encode($order));
    }

    public function test_models_are_read_only(): void
    {
        $order = Order::from(['code' => 'DH1']);

        $this->expectException(\LogicException::class);
        $order['code'] = 'DH2';
    }

    public function test_a_status_tells_a_new_order_from_a_replay(): void
    {
        $this->http->json(201, ['data' => ['code' => 'DH1']])->json(200, ['data' => ['code' => 'DH1']]);
        $client = $this->client();

        $client->orders->create(['package_id' => 1], 'K1');
        $first = $client->lastResponse();
        $client->orders->create(['package_id' => 1], 'K1');
        $second = $client->lastResponse();

        self::assertSame(201, $first?->getStatusCode());
        self::assertSame(200, $second?->getStatusCode());
    }
}
