<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 503: shop bảo trì (`maintenance`) hoặc dịch vụ tạm đóng (`deposit_unavailable`, …).
 */
final class ServiceUnavailableException extends ApiException
{
}
