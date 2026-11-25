<?php

declare(strict_types=1);

namespace Card2K\Model;

/**
 * Kết quả đổi phí ưu tiên Sò: `difference` (dương: đã trừ thêm, âm: đã hoàn)
 * và đơn sau khi đổi.
 *
 * Sinh từ resources/openapi.json (`QueuePriorityResponse.data`) bằng bin/generate-models.php.
 *
 * @property-read int $difference Dương: đã trừ thêm; âm: đã trả lại
 * @property-read Order $order
 */
final class QueuePriorityResult extends Model
{
    protected const NESTED = [
        'order' => Order::class,
    ];
}
