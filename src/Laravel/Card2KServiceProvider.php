<?php

declare(strict_types=1);

namespace Card2K\Laravel;

use Card2K\Client;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

/**
 * Laravel tự nạp (package discovery). Đăng ký `Card2K\Client` (một bản cho cả ứng dụng, từ
 * config/card2k.php) và `Route::card2kWebhook('/duong-dan')` ({@see WebhookRoute}).
 */
final class Card2KServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/card2k.php', 'card2k');

        $this->app->singleton(Client::class, static function (Application $app): Client {
            /** @var Config $config */
            $config = $app->make('config');
            $apiKey = trim((string) $config->get('card2k.api_key'));

            if ($apiKey === '') {
                throw new InvalidArgumentException('Thiếu CARD2K_API_KEY trong .env (key của kết nối API v3, Tài khoản → Kết nối API).');
            }

            $options = [
                'timeout' => (float) $config->get('card2k.timeout', 30),
                'max_retries' => (int) $config->get('card2k.max_retries', 2),
                'user_agent' => 'laravel/'.$app->version(),
            ];

            $baseUrl = trim((string) $config->get('card2k.base_url'));
            if ($baseUrl !== '') {
                $options['base_url'] = $baseUrl;
            }

            $channel = trim((string) $config->get('card2k.log_channel'));
            if ($channel !== '') {
                /** @var LogManager $log */
                $log = $app->make('log');
                $options['logger'] = $log->channel($channel);
            }

            return new Client($apiKey, $options);
        });

        $this->app->alias(Client::class, 'card2k');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../../config/card2k.php' => $this->app->configPath('card2k.php')], 'card2k-config');
        }

        Router::macro('card2kWebhook', fn (string $uri): RouteDefinition => WebhookRoute::register($uri));
    }
}
