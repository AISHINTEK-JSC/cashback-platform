<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Member = 'member';
    case Staff = 'staff';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Member => 'Thành viên',
            self::Staff => 'Nhân viên',
            self::Admin => 'Quản trị',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Member => 'gray',
            self::Staff => 'info',
            self::Admin => 'danger',
        };
    }
}
