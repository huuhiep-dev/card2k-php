<?php

declare(strict_types=1);

namespace Card2K\Model;

use ArrayAccess;
use JsonSerializable;
use LogicException;

/**
 * Một đối tượng API trả về (đơn, thẻ, mã nạp…). Đọc bằng thuộc tính (`$order->status`) hoặc như
 * mảng (`$order['status']`); `toArray()` trả nguyên dữ liệu JSON.
 *
 * Chỉ đọc. Field shop thêm sau này vẫn đọc được ngay bằng `$order->get('field_moi')` (hoặc
 * `$order['field_moi']`), không cần cập nhật SDK; field không có thì trả `null`.
 *
 * @implements ArrayAccess<string, mixed>
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
    /**
     * Field là đối tượng con: `'field' => Lop::class` (một) hoặc `'field' => [Lop::class]` (danh sách).
     *
     * @var array<string, class-string<Model>|array{class-string<Model>}>
     */
    protected const NESTED = [];

    /** @var array<string, mixed> */
    private array $attributes = [];

    /**
     * @param  array<string, mixed>  $raw  một đối tượng JSON đã giải mã
     */
    final public function __construct(private readonly array $raw)
    {
        foreach ($raw as $key => $value) {
            $this->attributes[$key] = $this->hydrate((string) $key, $value);
        }
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function from(array $raw): static
    {
        return new static($raw);
    }

    /**
     * @param  list<mixed>  $rows
     * @return list<static>
     */
    public static function listFrom(array $rows): array
    {
        $models = [];
        foreach ($rows as $row) {
            $models[] = new static(is_array($row) ? self::stringKeys($row) : []);
        }

        return $models;
    }

    /**
     * Một field theo tên, hoặc `$default`. Dùng cho field shop mới thêm mà SDK chưa khai báo
     * (PHPStan / IDE không biết `$order->field_moi`, nhưng `$order->get('field_moi')` thì được).
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * @throws LogicException luôn: đối tượng chỉ đọc
     */
    public function __set(string $name, mixed $value): void
    {
        throw new LogicException(static::class.' chỉ đọc.');
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    /**
     * @throws LogicException luôn: đối tượng chỉ đọc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException(static::class.' chỉ đọc.');
    }

    /**
     * @throws LogicException luôn: đối tượng chỉ đọc
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException(static::class.' chỉ đọc.');
    }

    /**
     * Nguyên dữ liệu JSON shop trả về.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->raw;
    }

    /**
     * Lớp đối tượng con của một field, cho các model cần quyết định lúc chạy (ví dụ `Event::$data`).
     *
     * @param  array<string, mixed>  $raw
     * @return class-string<Model>|array{class-string<Model>}|null
     */
    protected function nestedType(string $key, array $raw): string|array|null
    {
        return static::NESTED[$key] ?? null;
    }

    private function hydrate(string $key, mixed $value): mixed
    {
        $type = $this->nestedType($key, $this->raw);

        if ($type === null || ! is_array($value)) {
            return $value;
        }

        if (is_array($type)) {
            $class = $type[0];

            return $class::listFrom(array_values($value));
        }

        return new $type(self::stringKeys($value));
    }

    /**
     * @param  array<mixed>  $value
     * @return array<string, mixed>
     */
    private static function stringKeys(array $value): array
    {
        $result = [];
        foreach ($value as $key => $item) {
            $result[(string) $key] = $item;
        }

        return $result;
    }
}
