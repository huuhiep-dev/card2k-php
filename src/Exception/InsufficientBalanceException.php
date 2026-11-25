<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 402 `insufficient_balance`: ví không đủ tiền.
 */
final class InsufficientBalanceException extends ApiException
{
    /**
     * Số tiền cần (VND).
     */
    public function required(): ?int
    {
        $value = $this->getDetail('required');

        return is_int($value) ? $value : null;
    }

    /**
     * Số dư hiện có (VND).
     */
    public function balance(): ?int
    {
        $value = $this->getDetail('balance');

        return is_int($value) ? $value : null;
    }
}
