<?php

declare(strict_types=1);

namespace Card2K\Tests\Laravel;

use Card2K\Laravel\Card2KServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

abstract class LaravelTestCase extends TestCase
{
    protected const KEY = 'laravel-test-key';

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [Card2KServiceProvider::class];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return ['Card2K' => \Card2K\Laravel\Facades\Card2K::class];
    }

    /**
     * The app of the running test.
     */
    protected function laravel(): Application
    {
        self::assertNotNull($this->app);

        return $this->app;
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('card2k.api_key', self::KEY);
        $app['config']->set('cache.default', 'array');
        // The "web" group (cookies, session) needs an app key.
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
    }
}
