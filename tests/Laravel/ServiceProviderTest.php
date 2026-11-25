<?php

declare(strict_types=1);

namespace Card2K\Tests\Laravel;

use Card2K\Client;
use Card2K\Http\Transport;
use Card2K\Laravel\Facades\Card2K;
use Illuminate\Testing\PendingCommand;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

final class ServiceProviderTest extends LaravelTestCase
{
    private function transport(Client $client): Transport
    {
        $transport = (new ReflectionProperty(Client::class, 'transport'))->getValue($client);
        self::assertInstanceOf(Transport::class, $transport);

        return $transport;
    }

    private function transportValue(Client $client, string $property): mixed
    {
        return (new ReflectionProperty(Transport::class, $property))->getValue($this->transport($client));
    }

    public function test_the_client_comes_from_the_env_key_once_for_the_app(): void
    {
        $client = $this->laravel()->make(Client::class);

        self::assertSame($client, $this->laravel()->make(Client::class));
        self::assertSame($client, $this->laravel()->make('card2k'));
        self::assertSame($client->orders, Card2K::orders());
        self::assertSame($client->cardExchanges, Card2K::cardExchanges());
        self::assertSame('https://card2k.net/api/v3', $this->transportValue($client, 'baseUrl'));
        self::assertSame(self::KEY, $this->transportValue($client, 'apiKey'));
        self::assertNull($this->transportValue($client, 'logger'));
        self::assertStringContainsString(' laravel/', (string) $this->transportValue($client, 'userAgent'));
    }

    public function test_a_missing_key_says_what_to_put_in_env(): void
    {
        $this->laravel()['config']->set('card2k.api_key', null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CARD2K_API_KEY');

        $this->laravel()->make(Client::class);
    }

    public function test_base_url_retries_and_log_channel_are_read_from_config(): void
    {
        $this->laravel()['config']->set('card2k.base_url', 'https://shop-khac.example.com');
        $this->laravel()['config']->set('card2k.max_retries', 0);
        $this->laravel()['config']->set('card2k.log_channel', 'single');

        $client = $this->laravel()->make(Client::class);

        self::assertSame('https://shop-khac.example.com/api/v3', $this->transportValue($client, 'baseUrl'));
        self::assertSame(0, $this->transportValue($client, 'maxRetries'));
        self::assertInstanceOf(LoggerInterface::class, $this->transportValue($client, 'logger'));
    }

    public function test_the_config_can_be_published(): void
    {
        $command = $this->artisan('vendor:publish', ['--tag' => 'card2k-config']);
        self::assertInstanceOf(PendingCommand::class, $command);
        $command->assertSuccessful()->run();

        self::assertFileExists($this->laravel()->configPath('card2k.php'));
        @unlink($this->laravel()->configPath('card2k.php'));
    }
}
