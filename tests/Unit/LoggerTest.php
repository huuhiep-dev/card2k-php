<?php

declare(strict_types=1);

namespace Card2K\Tests\Unit;

use Card2K\ErrorCode;
use Card2K\Tests\Support\TestCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\AbstractLogger;
use Stringable;

final class LoggerTest extends TestCase
{
    public function test_each_call_is_logged_without_key_body_or_card_code(): void
    {
        $logger = new class extends AbstractLogger
        {
            /** @var list<array{level: mixed, message: string, context: array<mixed>}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };
        $factory = new Psr17Factory();
        $client = new \Card2K\Client(self::KEY, [
            'http_client' => $this->http,
            'request_factory' => $factory,
            'stream_factory' => $factory,
            'sleep' => fn (float $seconds) => null,
            'logger' => $logger,
        ]);
        $this->error(429, ErrorCode::RATE_LIMITED, [], ['Retry-After' => '1', 'X-Request-Id' => 'req-a']);
        $this->http->json(201, ['data' => ['id' => '1', 'reference' => 'THE-1']], ['X-Request-Id' => 'req-b']);

        $client->cardExchanges->create(['telco' => 'VIETTEL', 'value' => 10000, 'serial' => '1000123', 'code' => 'MA-THE-BI-MAT', 'reference' => 'THE-1']);

        self::assertCount(2, $logger->records);
        self::assertSame('warning', $logger->records[0]['level']);
        self::assertSame('info', $logger->records[1]['level']);
        self::assertSame(
            ['method' => 'POST', 'path' => '/card-exchanges', 'status' => 201, 'request_id' => 'req-b', 'attempt' => 2],
            array_diff_key($logger->records[1]['context'], ['ms' => true]),
        );
        $logged = (string) json_encode($logger->records);
        self::assertStringNotContainsString(self::KEY, $logged);
        self::assertStringNotContainsString('MA-THE-BI-MAT', $logged);
    }
}
