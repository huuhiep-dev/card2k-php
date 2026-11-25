<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 404: không có, hoặc không thuộc kết nối này (`order_not_found`, `card_not_found`, …).
 */
final class NotFoundException extends ApiException
{
}
