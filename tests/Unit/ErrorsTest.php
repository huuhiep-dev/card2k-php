<?php

declare(strict_types=1);

namespace Card2K\Tests\Unit;

use Card2K\ErrorCode;
use Card2K\Exception\ApiException;
use Card2K\Exception\AuthenticationException;
use Card2K\Exception\BadRequestException;
use Card2K\Exception\Card2KException;
use Card2K\Exception\ConflictException;
use Card2K\Exception\InsufficientBalanceException;
use Card2K\Exception\LockedException;
use Card2K\Exception\NotFoundException;
use Card2K\Exception\PermissionException;
use Card2K\Exception\RateLimitException;
use Card2K\Exception\ServerException;
use Card2K\Exception\ServiceUnavailableException;
use Card2K\Exception\ValidationException;
use Card2K\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ErrorsTest extends TestCase
{
    /**
     * @return array<string, array{int, string, class-string<ApiException>}>
     */
    public static function statuses(): array
    {
        return [
            'HTTP 400' => [400, ErrorCode::IDEMPOTENCY_KEY_REQUIRED, BadRequestException::class],
            'HTTP 401' => [401, ErrorCode::INVALID_API_KEY, AuthenticationException::class],
            'HTTP 402' => [402, ErrorCode::INSUFFICIENT_BALANCE, InsufficientBalanceException::class],
            'HTTP 403' => [403, ErrorCode::SCOPE_NOT_GRANTED, PermissionException::class],
            'HTTP 404' => [404, ErrorCode::ORDER_NOT_FOUND, NotFoundException::class],
            'HTTP 409' => [409, ErrorCode::WARRANTY_CLAIM_OPEN, ConflictException::class],
            'HTTP 422' => [422, ErrorCode::VALIDATION_FAILED, ValidationException::class],
            'HTTP 423' => [423, ErrorCode::CARD_EXCHANGE_LOCKED, LockedException::class],
            'HTTP 429' => [429, ErrorCode::RATE_LIMITED, RateLimitException::class],
            'HTTP 500' => [500, ErrorCode::SERVER_ERROR, ServerException::class],
            'HTTP 503' => [503, ErrorCode::MAINTENANCE, ServiceUnavailableException::class],
            'HTTP 405' => [405, ErrorCode::METHOD_NOT_ALLOWED, ApiException::class],
        ];
    }

    /**
     * @param  class-string<ApiException>  $class
     */
    #[DataProvider('statuses')]
    public function test_each_status_has_its_exception_with_code_and_request_id(int $status, string $code, string $class): void
    {
        $this->error($status, $code, [], ['X-Request-Id' => 'req-42']);

        try {
            $this->client(['max_retries' => 0])->orders->get('DH1');
            self::fail('Phải ném lỗi.');
        } catch (ApiException $exception) {
            self::assertSame($class, $exception::class);
            self::assertInstanceOf(Card2KException::class, $exception);
            self::assertSame($status, $exception->getStatusCode());
            self::assertSame($code, $exception->getErrorCode());
            self::assertSame('Lỗi '.$code, $exception->getMessage());
            self::assertSame('req-42', $exception->getRequestId());
        }
    }

    public function test_insufficient_balance_tells_how_much_is_needed(): void
    {
        $this->error(402, ErrorCode::INSUFFICIENT_BALANCE, ['required' => 50000, 'balance' => 20000]);

        try {
            $this->client()->orders->create(['package_id' => 1], 'K');
            self::fail('Phải ném lỗi.');
        } catch (InsufficientBalanceException $exception) {
            self::assertSame(50000, $exception->required());
            self::assertSame(20000, $exception->balance());
            self::assertSame(['required' => 50000, 'balance' => 20000], $exception->getDetails());
        }
    }

    public function test_validation_lists_errors_by_field(): void
    {
        $this->error(422, ErrorCode::VALIDATION_FAILED, ['fields' => ['amount' => ['Tối thiểu 1.000đ.']]]);

        try {
            $this->client()->wallet->transfer(500, 'K');
            self::fail('Phải ném lỗi.');
        } catch (ValidationException $exception) {
            self::assertSame(['amount' => ['Tối thiểu 1.000đ.']], $exception->fields());
        }
    }

    public function test_a_lock_tells_until_when(): void
    {
        $this->error(423, ErrorCode::CARD_EXCHANGE_LOCKED, ['locked_until' => '2026-11-26T12:00:00+07:00']);

        try {
            $this->client()->cardExchanges->create(['telco' => 'VIETTEL', 'value' => 10000, 'serial' => 's', 'code' => 'c']);
            self::fail('Phải ném lỗi.');
        } catch (LockedException $exception) {
            self::assertSame('2026-11-26T12:00:00+07:00', $exception->lockedUntil()?->format(DATE_ATOM));
        }
    }

    public function test_a_non_json_error_page_still_gives_an_api_exception(): void
    {
        $this->http->raw(502, '<html>Bad Gateway</html>')->raw(502, '<html>Bad Gateway</html>')->raw(502, '<html>Bad Gateway</html>');

        try {
            $this->client()->account->get();
            self::fail('Phải ném lỗi.');
        } catch (ServerException $exception) {
            self::assertSame(ErrorCode::HTTP_ERROR, $exception->getErrorCode());
            self::assertSame(502, $exception->getStatusCode());
        }
    }

    public function test_a_success_that_is_not_json_is_an_error(): void
    {
        $this->http->raw(200, 'ok');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('không phải JSON');

        $this->client()->account->get();
    }
}
