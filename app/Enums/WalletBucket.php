<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WalletBucket: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Available = 'available';
    case Frozen = 'frozen';

    /**
     * Cột số dư tương ứng trong bảng wallets.
     */
    public function column(): string
    {
        return $this->value.'_balance';
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Chờ duyệt',
            self::Available => 'Khả dụng',
            self::Frozen => 'Đang rút',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Available => 'success',
            self::Frozen => 'info',
        };
    }
}
