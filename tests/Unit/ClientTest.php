<?php

declare(strict_types=1);

namespace Card2K\Tests\Unit;

use Card2K\Client;
use Card2K\Tests\Support\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

final class ClientTest extends TestCase
{
    public function test_requests_go_to_card2k_with_the_key_and_user_agent(): void
    {
        $this->http->json(200, ['data' => []]);

        $this->client(['user_agent' => 'shop-cua-toi/1.0'])->account->get();

        $request = $this->http->last();
        self::assertSame('https://card2k.net/api/v3/me', (string) $request->getUri());
        self::assertSame('Bearer '.self::KEY, $request->getHeaderLine('Authorization'));
        self::assertSame('card2k-php/'.Client::VERSION.' PHP/'.PHP_VERSION.' shop-cua-toi/1.0', $request->getHeaderLine('User-Agent'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function baseUrls(): array
    {
        return [
            'domain' => ['https://shop.example.com', 'https://shop.example.com/api/v3/me'],
            'trailing slash' => ['https://shop.example.com/', 'https://shop.example.com/api/v3/me'],
            'with /api/v3' => ['https://shop.example.com/api/v3', 'https://shop.example.com/api/v3/me'],
            'local http' => ['http://card2k.test', 'http://card2k.test/api/v3/me'],
            'localhost port' => ['http://127.0.0.1:8000', 'http://127.0.0.1:8000/api/v3/me'],
        ];
    }

    #[DataProvider('baseUrls')]
    public function test_the_base_url_can_be_changed(string $baseUrl, string $expected): void
    {
        $this->http->json(200, ['data' => []]);

        $this->client(['base_url' => $baseUrl])->account->get();

        self::assertSame($expected, (string) $this->http->last()->getUri());
    }

    public function test_http_is_refused_outside_local_machines(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('https');

        $this->client(['base_url' => 'http://card2k.net']);
    }

    public function test_a_broken_base_url_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->client(['base_url' => 'card2k.net']);
    }

    public function test_an_empty_key_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Client('  ');
    }

    protected function tearDown(): void
    {
        unset($_ENV[Client::ENV_KEY], $_SERVER[Client::ENV_KEY]);
        putenv(Client::ENV_KEY);
        parent::tearDown();
    }

    /**
     * Key giả từ một nơi trong môi trường.
     */
    private function clientFromEnvironment(): Client
    {
        $factory = new \Nyholm\Psr7\Factory\Psr17Factory();

        return new Client(options: ['http_client' => $this->http, 'request_factory' => $factory, 'stream_factory' => $factory]);
    }

    private function sentKey(Client $client): string
    {
        $this->http->json(200, ['data' => []]);
        $client->account->get();

        return $this->http->last()->getHeaderLine('Authorization');
    }

    public function test_without_a_key_it_reads_card2k_api_key_from_the_environment(): void
    {
        $_ENV[Client::ENV_KEY] = 'key-tu-env';
        self::assertSame('Bearer key-tu-env', $this->sentKey($this->clientFromEnvironment()));
        unset($_ENV[Client::ENV_KEY]);

        $_SERVER[Client::ENV_KEY] = 'key-tu-server';
        self::assertSame('Bearer key-tu-server', $this->sentKey($this->clientFromEnvironment()));
        unset($_SERVER[Client::ENV_KEY]);

        putenv(Client::ENV_KEY.'=key-tu-getenv');
        self::assertSame('Bearer key-tu-getenv', $this->sentKey($this->clientFromEnvironment()));
    }

    public function test_a_key_given_wins_over_the_environment(): void
    {
        $_ENV[Client::ENV_KEY] = 'key-tu-env';

        self::assertSame('Bearer '.self::KEY, $this->sentKey($this->client()));
    }

    public function test_no_key_anywhere_says_where_to_put_it(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CARD2K_API_KEY');

        $this->clientFromEnvironment();
    }

    public function test_without_a_client_guzzle_is_used_with_a_timeout(): void
    {
        $client = new Client(self::KEY, ['timeout' => 12]);

        $transport = (new \ReflectionProperty(Client::class, 'transport'))->getValue($client);
        $http = (new \ReflectionProperty($transport, 'http'))->getValue($transport);

        self::assertInstanceOf(\GuzzleHttp\Client::class, $http);
        $config = (new \ReflectionProperty($http, 'config'))->getValue($http);
        self::assertIsArray($config);
        self::assertSame(12.0, $config['timeout']);
        self::assertSame(10.0, $config['connect_timeout']);
        self::assertNull($client->lastResponse());
    }
}
