<?php

declare(strict_types=1);

namespace Card2K\Tests\Unit;

use Card2K\ErrorCode;
use Card2K\Exception\ConflictException;
use Card2K\Exception\NetworkException;
use Card2K\Exception\RateLimitException;
use Card2K\Exception\ServiceUnavailableException;
use Card2K\Exception\ValidationException;
use Card2K\Tests\Support\TestCase;

final class RetryTest extends TestCase
{
    public function test_a_429_waits_for_retry_after_then_succeeds(): void
    {
        $this->error(429, ErrorCode::RATE_LIMITED, [], ['Retry-After' => '3']);
        $this->http->json(200, ['data' => ['balance' => 1000]]);

        $account = $this->client()->account->get();

        self::assertSame(1000, $account->balance);
        self::assertSame([3.0], $this->sleeps);
        self::assertCount(2, $this->http->requests);
    }

    public function test_a_retry_after_longer_than_the_limit_is_not_waited_for(): void
    {
        $this->error(429, ErrorCode::RATE_LIMITED, [], ['Retry-After' => '45']);

        try {
            $this->client()->account->get();
            self::fail('Phải ném RateLimitException.');
        } catch (RateLimitException $exception) {
            self::assertSame(45, $exception->retryAfter());
        }

        self::assertSame([], $this->sleeps);
        self::assertCount(1, $this->http->requests);
    }

    public function test_a_network_error_is_retried_with_the_same_idempotency_key(): void
    {
        $this->http->fail($this->networkError())->json(201, ['data' => ['code' => 'DH1']]);

        $order = $this->client()->orders->create(['package_id' => 1]);

        self::assertSame('DH1', $order->code);
        self::assertCount(2, $this->http->requests);
        $keys = array_map(fn ($request) => $request->getHeaderLine('Idempotency-Key'), $this->http->requests);
        self::assertSame($keys[0], $keys[1]);
        self::assertNotSame('', $keys[0]);
        self::assertCount(1, $this->sleeps);
        self::assertGreaterThan(0.3, $this->sleeps[0]);
    }

    public function test_after_the_last_retry_the_network_error_gives_the_key_back(): void
    {
        $this->http->fail($this->networkError())->fail($this->networkError())->fail($this->networkError());

        try {
            $this->client()->orders->create(['package_id' => 1], 'DON-7');
            self::fail('Phải ném NetworkException.');
        } catch (NetworkException $exception) {
            self::assertSame('DON-7', $exception->getIdempotencyKey());
            self::assertStringContainsString('Connection timed out', $exception->getMessage());
        }

        self::assertCount(3, $this->http->requests);
        self::assertCount(2, $this->sleeps);
        self::assertGreaterThan($this->sleeps[0], $this->sleeps[1]);
    }

    public function test_503_and_request_in_progress_are_retried(): void
    {
        $this->error(503, ErrorCode::MAINTENANCE);
        $this->error(409, ErrorCode::REQUEST_IN_PROGRESS);
        $this->http->json(201, ['data' => ['code' => 'DH1']]);

        self::assertSame('DH1', $this->client()->orders->create(['package_id' => 1], 'K')->code);
        self::assertCount(3, $this->http->requests);
    }

    public function test_errors_about_the_request_itself_are_not_retried(): void
    {
        $this->error(422, ErrorCode::VALIDATION_FAILED);

        $this->expectException(ValidationException::class);

        try {
            $this->client()->orders->create(['package_id' => 1], 'K');
        } finally {
            self::assertCount(1, $this->http->requests);
            self::assertSame([], $this->sleeps);
        }
    }

    public function test_another_409_is_not_retried(): void
    {
        $this->error(409, ErrorCode::WARRANTY_CLAIM_OPEN, ['claim_id' => 4]);

        try {
            $this->client()->warrantyClaims->create('DH1', ['item_ids' => [1], 'zalo_phone' => '0900000000']);
            self::fail('Phải ném ConflictException.');
        } catch (ConflictException $exception) {
            self::assertSame(4, $exception->getDetail('claim_id'));
        }
        self::assertCount(1, $this->http->requests);
    }

    public function test_retries_can_be_turned_off(): void
    {
        $this->error(503, ErrorCode::MAINTENANCE);

        $this->expectException(ServiceUnavailableException::class);

        $this->client(['max_retries' => 0])->account->get();
    }

    public function test_a_retry_after_date_is_understood(): void
    {
        $this->error(503, ErrorCode::MAINTENANCE, [], ['Retry-After' => gmdate('D, d M Y H:i:s', time() + 5).' GMT']);
        $this->http->json(200, ['data' => []]);

        $this->client()->account->get();

        self::assertCount(1, $this->sleeps);
        self::assertGreaterThanOrEqual(3.0, $this->sleeps[0]);
        self::assertLessThanOrEqual(6.0, $this->sleeps[0]);
    }
}
