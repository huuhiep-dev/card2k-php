<?php

declare(strict_types=1);

namespace Card2K\Laravel;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Route nhận webhook: `Route::card2kWebhook('/card2k/webhook')`, hoặc
 * `WebhookRoute::register('/card2k/webhook')` (cùng việc, IDE / PHPStan hiểu kiểu).
 */
final class WebhookRoute
{
    private function __construct()
    {
    }

    public static function register(string $uri): Route
    {
        // The shop calls it from its server: no CSRF token (VerifyCsrfToken up to Laravel 10,
        // ValidateCsrfToken from 11; a subclass in the app is excluded too).
        $csrf = array_values(array_filter(
            [VerifyCsrfToken::class, ValidateCsrfToken::class],
            static fn (string $class): bool => class_exists($class),
        ));

        return RouteFacade::post($uri, WebhookController::class)
            ->name('card2k.webhook')
            ->withoutMiddleware($csrf);
    }
}
