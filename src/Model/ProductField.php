<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một ô khách phải điền khi đặt (ID game, server…), gửi trong `fields` theo `id`.
 *
 * Sinh từ resources/openapi.json (`ProductField`) bằng bin/generate-models.php.
 *
 * @property-read int $id
 * @property-read string $label
 * @property-read string $type
 * @property-read bool $required
 * @property-read list<array{value: string, label: string}> $options
 */
final class ProductField extends Model
{
}
