<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Một thẻ cào đã giao (mã thẻ và serial).
 *
 * Sinh từ resources/openapi.json (`Card`) bằng bin/generate-models.php.
 *
 * @property-read string|null $serial
 * @property-read string $code
 */
final class Card extends Model
{
}
