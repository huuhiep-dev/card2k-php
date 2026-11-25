<?php

declare(strict_types=1);

namespace Card2K\Tests\Unit;

use Card2K\Model\CardExchange;
use Card2K\Model\Deposit;
use Card2K\Model\Order;
use Card2K\Webhook\SignatureVerificationException;
use Card2K\Webhook\Webhook;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class WebhookTest extends TestCase
{
    private const KEY = 'whsec-key';

    private const NOW = 1_795_600_000;

    /**
     * Header như shop gửi (xem mục Webhook của tài liệu API v3).
     *
     * @return array<string, string>
     */
    private static function headers(string $body, int $timestamp = self::NOW, string $key = self::KEY): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-Webhook-Event' => 'order.updated',
            'X-Webhook-Id' => '9b2f6a4e-3c1d-4f8a-9e7b-1a2b3c4d5e6f',
            'X-Webhook-Timestamp' => (string) $timestamp,
            'X-Signature' => 'sha256='.hash_hmac('sha256', $body, $key),
            'X-Signature-V2' => 'sha256='.hash_hmac('sha256', "{$timestamp}.{$body}", $key),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function body(string $event, array $data): string
    {
        return (string) json_encode(['event' => $event, 'event_id' => '9b2f6a4e-3c1d-4f8a-9e7b-1a2b3c4d5e6f', 'created_at' => '2026-11-26T10:00:00+07:00', 'data' => $data], JSON_UNESCAPED_UNICODE);
    }

    public function test_a_signed_webhook_gives_its_event_with_typed_data(): void
    {
        $cases = ['order.updated' => Order::class, 'card_exchange.updated' => CardExchange::class, 'deposit.completed' => Deposit::class];

        foreach ($cases as $type => $class) {
            $body = self::body($type, ['code' => 'Mã đơn']);
            $event = Webhook::constructEvent($body, self::headers($body), self::KEY, now: self::NOW + 10);

            self::assertSame($type, $event->event);
            self::assertInstanceOf($class, $event->data);
            self::assertSame('9b2f6a4e-3c1d-4f8a-9e7b-1a2b3c4d5e6f', $event->event_id);
        }
    }

    public function test_headers_in_any_case_or_from_server_vars_work(): void
    {
        $body = self::body('webhook.test', ['partner_id' => 'P1', 'message' => 'Xin chào']);
        $server = [];
        foreach (self::headers($body) as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }
        $lower = array_change_key_case(self::headers($body), CASE_LOWER);

        self::assertSame(['partner_id' => 'P1', 'message' => 'Xin chào'], Webhook::constructEvent($body, $server, self::KEY, now: self::NOW)->data);
        self::assertSame('webhook.test', Webhook::constructEvent($body, $lower, self::KEY, now: self::NOW)->event);
    }

    public function test_a_psr7_request_works(): void
    {
        $body = self::body('order.updated', ['code' => 'DH1']);
        $request = new ServerRequest('POST', 'https://doitac.example.com/hook', self::headers($body), $body);

        $event = Webhook::constructEventFromRequest($request, self::KEY, now: self::NOW);

        self::assertSame('DH1', $event->data instanceof Order ? $event->data->code : null);
    }

    /**
     * @return array<string, array{callable(string, array<string, string>): array{string, array<string, string>}, string}>
     */
    public static function refusals(): array
    {
        return [
            'wrong key' => [fn (string $b, array $h) => [$b, self::headers($b, key: 'khac')], 'Chữ ký webhook không đúng'],
            'body changed' => [fn (string $b, array $h) => [str_replace('DH1', 'DH2', $b), $h], 'Chữ ký webhook không đúng'],
            'too old' => [fn (string $b, array $h) => [$b, self::headers($b, self::NOW - 301)], 'quá cũ'],
            'from the future' => [fn (string $b, array $h) => [$b, self::headers($b, self::NOW + 301)], 'quá cũ'],
            'only the old signature' => [fn (string $b, array $h) => [$b, array_diff_key($h, ['X-Signature-V2' => 1])], 'Thiếu header'],
            'timestamp changed' => [fn (string $b, array $h) => [$b, ['X-Webhook-Timestamp' => (string) (self::NOW + 1)] + $h], 'Chữ ký webhook không đúng'],
            'not json' => [fn (string $b, array $h) => ['khong-phai-json', self::headers('khong-phai-json')], 'không phải JSON'],
            'no event' => [fn (string $b, array $h) => ['{"data":{}}', self::headers('{"data":{}}')], 'không có `event`'],
        ];
    }

    /**
     * @param  callable(string, array<string, string>): array{string, array<string, string>}  $tamper
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('refusals')]
    public function test_a_bad_webhook_is_refused(callable $tamper, string $message): void
    {
        $body = self::body('order.updated', ['code' => 'DH1']);
        [$body, $headers] = $tamper($body, self::headers($body));

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage($message);

        Webhook::constructEvent($body, $headers, self::KEY, now: self::NOW);
    }

    public function test_the_time_check_can_be_widened(): void
    {
        $body = self::body('order.updated', ['code' => 'DH1']);

        $event = Webhook::constructEvent($body, self::headers($body, self::NOW - 3600), self::KEY, 7200, self::NOW);

        self::assertSame('order.updated', $event->event);
    }
}
