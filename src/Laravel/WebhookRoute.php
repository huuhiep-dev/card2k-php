<?php

declare(strict_types=1);

namespace Card2K\Laravel;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Route nhận webhook: `Route::card2kWebhook('/card2k/webhook')`, hoặc
 * `WebhookRoute::register('/card2k/webhook')` (cùng việc, IDE / PHPStan hiểu kiểu).
 */
final class WebhookRoute
{
    /**
     * Laravel's request-forgery (CSRF) middleware across versions: PreventRequestForgery from
     * Laravel 13 (the parent of the two others there), ValidateCsrfToken in 11 and 12,
     * VerifyCsrfToken up to 10. Excluding a class excludes its subclasses too (the app's own
     * VerifyCsrfToken). Names as strings: not every class exists in every version.
     */
    public const CSRF_MIDDLEWARE = [
        'Illuminate\Foundation\Http\Middleware\PreventRequestForgery',
        'Illuminate\Foundation\Http\Middleware\ValidateCsrfToken',
        'Illuminate\Foundation\Http\Middleware\VerifyCsrfToken',
    ];

    private function __construct()
    {
    }

    public static function register(string $uri): Route
    {
        // The shop calls it from its server, with no CSRF token: the signature protects it.
        $csrf = array_values(array_filter(self::CSRF_MIDDLEWARE, static fn (string $class): bool => class_exists($class)));

        return RouteFacade::post($uri, WebhookController::class)
            ->name('card2k.webhook')
            ->withoutMiddleware($csrf);
    }
}
