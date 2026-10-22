<?php

namespace App\Exceptions;

class InsufficientBalanceException extends BusinessRuleException
{
    public static function make(): self
    {
        return new self('Số dư không đủ để thực hiện giao dịch.');
    }
}
