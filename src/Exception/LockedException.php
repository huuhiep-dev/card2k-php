<?php

declare(strict_types=1);

namespace Card2K\Exception;

use DateTimeImmutable;

/**
 * HTTP 423 `card_exchange_locked`: gửi quá nhiều thẻ sai nên bị khóa đổi thẻ tạm thời.
 */
final class LockedException extends ApiException
{
    /**
     * Lúc hết khóa, hoặc null.
     */
    public function lockedUntil(): ?DateTimeImmutable
    {
        $value = $this->getDetail('locked_until');
        if (! is_string($value)) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}
