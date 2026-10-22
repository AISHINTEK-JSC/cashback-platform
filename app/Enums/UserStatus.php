<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Locked = 'locked';
    case Pending = 'pending';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Hoạt động',
            self::Locked => 'Bị khoá',
            self::Pending => 'Chờ kích hoạt',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Locked => 'danger',
            self::Pending => 'warning',
        };
    }
}
