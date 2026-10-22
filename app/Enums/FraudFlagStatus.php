<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FraudFlagStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Ignored = 'ignored';
    case Confirmed = 'confirmed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Chờ xem xét',
            self::Ignored => 'Bỏ qua',
            self::Confirmed => 'Xác nhận gian lận',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Ignored => 'gray',
            self::Confirmed => 'danger',
        };
    }
}
