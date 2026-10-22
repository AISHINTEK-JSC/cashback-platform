<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Lỗi nghiệp vụ có thông điệp hiển thị được cho người dùng (số dư không đủ, vượt giới hạn...).
 */
class BusinessRuleException extends RuntimeException {}
