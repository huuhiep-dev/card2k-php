<?php

declare(strict_types=1);

namespace Card2K\Exception;

/**
 * HTTP 403: key không được làm việc này: `scope_not_granted` (bật quyền ở Tài khoản → Kết nối API),
 * `connection_disabled`, `connection_locked`, `account_blocked`, `ip_not_allowed`,
 * `phone_verification_required`, `wrong_connection_type`.
 */
final class PermissionException extends ApiException
{
}
