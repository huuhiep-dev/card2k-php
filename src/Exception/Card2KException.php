<?php

declare(strict_types=1);

namespace Card2K\Exception;

use RuntimeException;

/**
 * Lớp gốc của mọi lỗi SDK ném ra: một `catch (Card2KException $e)` bắt được tất cả.
 */
class Card2KException extends RuntimeException
{
}
