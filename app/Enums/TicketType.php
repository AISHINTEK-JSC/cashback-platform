<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketType: string implements HasColor, HasLabel
{
    case MissingOrder = 'missing_order';
    case WrongAmount = 'wrong_amount';
    case Withdraw = 'withdraw';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::MissingOrder => 'Đơn thiếu',
            self::WrongAmount => 'Sai số tiền',
            self::Withdraw => 'Rút tiền',
            self::Other => 'Khác',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::MissingOrder => 'warning',
            self::WrongAmount => 'warning',
            self::Withdraw => 'info',
            self::Other => 'gray',
        };
    }
}
