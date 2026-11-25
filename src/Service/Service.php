<?php

declare(strict_types=1);

namespace Card2K\Service;

use Card2K\Http\Transport;
use Card2K\Model\Model;
use Card2K\Page;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;

/**
 * Phần chung của các service.
 *
 * @internal
 */
abstract class Service
{
    final public function __construct(protected readonly Transport $transport)
    {
    }

    /**
     * Tạo một Idempotency-Key / reference ngẫu nhiên (32 ký tự hex).
     */
    public static function newKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * `data` của phản hồi.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    protected function data(array $response): array
    {
        $data = $response['data'] ?? [];
        $result = [];
        foreach (is_array($data) ? $data : [] as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * `data` là danh sách.
     *
     * @param  array<string, mixed>  $response
     * @return list<mixed>
     */
    protected function rows(array $response): array
    {
        $data = $response['data'] ?? [];

        return is_array($data) ? array_values($data) : [];
    }

    /**
     * @template T of Model
     *
     * @param  array<string, mixed>  $response
     * @param  class-string<T>  $class
     * @return Page<T>
     */
    protected function page(array $response, string $class): Page
    {
        $meta = is_array($response['meta'] ?? null) ? $response['meta'] : [];
        $int = static fn (string $key, int $default): int => is_int($meta[$key] ?? null) ? $meta[$key] : $default;

        return new Page(
            $class::listFrom($this->rows($response)),
            $int('page', 1),
            $int('per_page', 20),
            $int('total', 0),
            $int('last_page', 1),
        );
    }

    /**
     * Duyệt mọi trang, bắt đầu từ `page` trong bộ lọc (mặc định 1).
     *
     * @template T
     *
     * @param  array<string, mixed>  $filters
     * @param  callable(array<string, mixed>): Page<T>  $fetch
     * @return Generator<int, T>
     */
    protected function eachPage(array $filters, callable $fetch): Generator
    {
        $page = is_int($filters['page'] ?? null) ? $filters['page'] : 1;

        do {
            $result = $fetch(['page' => $page] + $filters);
            foreach ($result->data as $item) {
                yield $item;
            }
            $page = $result->page + 1;
        } while ($result->hasMore() && $result->data !== []);
    }

    /**
     * Thời điểm cho tham số lọc: `DateTimeInterface` đổi sang UTC (`2026-11-25T03:00:00Z`), chuỗi
     * gửi nguyên văn.
     *
     * Gửi UTC vì shop trước v2.20.2 so mốc có múi khác UTC (ví dụ `+07:00`) lệch 7 tiếng; UTC
     * thì đúng với mọi bản.
     */
    protected static function time(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value)
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z');
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Giá trị chuỗi / số của bộ lọc (bỏ qua giá trị khác).
     *
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $keys
     * @return array<string, scalar|null>
     */
    protected static function pick(array $filters, array $keys): array
    {
        $query = [];
        foreach ($keys as $key) {
            $value = $filters[$key] ?? null;
            $query[$key] = is_scalar($value) ? $value : self::time($value);
        }

        return $query;
    }

    protected static function segment(string|int $value): string
    {
        return rawurlencode((string) $value);
    }
}
