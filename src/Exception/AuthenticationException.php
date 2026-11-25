<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 401: thiếu key (`missing_api_key`) hoặc key sai (`invalid_api_key`).
 */
final class AuthenticationException extends ApiException
{
}
