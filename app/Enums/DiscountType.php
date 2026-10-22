<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DiscountType: string implements HasColor, HasLabel
{
    case Percent = 'percent';
    case Fixed = 'fixed';
    case Freeship = 'freeship';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percent => 'Giảm %',
            self::Fixed => 'Giảm tiền',
            self::Freeship => 'Freeship',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Percent => 'primary',
            self::Fixed => 'success',
            self::Freeship => 'info',
        };
    }
}
