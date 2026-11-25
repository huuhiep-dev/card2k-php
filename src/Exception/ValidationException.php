<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 422: dữ liệu gửi lên không dùng được (`validation_failed`, `invalid_fields`, `voucher_refused`,
 * `idempotency_key_reused`, `reference_reused`, `invalid_card_format`, …).
 */
final class ValidationException extends ApiException
{
    /**
     * Lỗi theo từng ô khi `validation_failed`, ví dụ `['amount' => ['…']]`.
     *
     * @return array<string, list<string>>
     */
    public function fields(): array
    {
        $fields = $this->getDetail('fields');
        if (! is_array($fields)) {
            return [];
        }

        $result = [];
        foreach ($fields as $name => $messages) {
            $result[(string) $name] = array_values(array_map('strval', (array) $messages));
        }

        return $result;
    }
}
