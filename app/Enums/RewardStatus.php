<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RewardStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Available = 'available';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Chờ duyệt',
            self::Available => 'Khả dụng',
            self::Cancelled => 'Đã huỷ',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Available => 'success',
            self::Cancelled => 'danger',
        };
    }
}
