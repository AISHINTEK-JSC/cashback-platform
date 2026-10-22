<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RewardType: string implements HasColor, HasLabel
{
    case Self = 'self';
    case F1 = 'f1';
    case F2 = 'f2';

    public function getLabel(): string
    {
        return match ($this) {
            self::Self => 'Hoàn tiền mua hàng',
            self::F1 => 'Hoa hồng F1',
            self::F2 => 'Hoa hồng F2',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Self => 'primary',
            self::F1 => 'info',
            self::F2 => 'gray',
        };
    }
}
