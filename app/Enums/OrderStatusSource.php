<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatusSource: string implements HasColor, HasLabel
{
    case Sync = 'sync';
    case Admin = 'admin';
    case System = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sync => 'Đồng bộ',
            self::Admin => 'Quản trị',
            self::System => 'Hệ thống',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Sync => 'info',
            self::Admin => 'warning',
            self::System => 'gray',
        };
    }
}
