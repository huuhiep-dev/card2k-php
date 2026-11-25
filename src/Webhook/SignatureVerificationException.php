<?php

declare(strict_types=1);

namespace Card2K\Webhook;

use Card2K\Exception\Card2KException;

/**
 * Webhook không đúng chữ ký, quá cũ, hoặc không đúng dạng. Trả HTTP 400 và bỏ qua nó.
 */
final class SignatureVerificationException extends Card2KException
{
}
