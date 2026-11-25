<?php

declare(strict_types=1);

namespace Card2K\Tests\Support;

use Card2K\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected const KEY = 'test-key-abc123';

    protected FakeHttp $http;

    /** @var list<float> số giây SDK đã chờ trước mỗi lần thử lại */
    protected array $sleeps = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = new FakeHttp();
        $this->sleeps = [];
    }

    /**
     * @param  array{base_url?: string, max_retries?: int, max_retry_delay?: int|float, user_agent?: string}  $options
     */
    protected function client(array $options = []): Client
    {
        $factory = new Psr17Factory();

        return new Client(self::KEY, $options + [
            'http_client' => $this->http,
            'request_factory' => $factory,
            'stream_factory' => $factory,
            'sleep' => function (float $seconds): void {
                $this->sleeps[] = $seconds;
            },
        ]);
    }

    /**
     * Lỗi mạng giả.
     */
    protected function networkError(): NetworkError
    {
        return new NetworkError(new Request('GET', 'https://card2k.net/api/v3/me'));
    }

    /**
     * Phản hồi lỗi API.
     *
     * @param  array<string, mixed>  $details
     * @param  array<string, string>  $headers
     */
    protected function error(int $status, string $code, array $details = [], array $headers = []): FakeHttp
    {
        return $this->http->json($status, ['error' => ['code' => $code, 'message' => 'Lỗi '.$code] + $details], $headers);
    }
}
