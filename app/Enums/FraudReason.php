<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FraudReason: string implements HasColor, HasLabel
{
    case SharedBank = 'shared_bank';
    case SharedDevice = 'shared_device';
    case SelfReferral = 'self_referral';
    case HighCancelRate = 'high_cancel_rate';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::SharedBank => 'Trùng tài khoản ngân hàng',
            self::SharedDevice => 'Trùng thiết bị',
            self::SelfReferral => 'Tự giới thiệu',
            self::HighCancelRate => 'Tỷ lệ huỷ cao',
            self::Other => 'Khác',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SharedBank => 'danger',
            self::SharedDevice => 'warning',
            self::SelfReferral => 'danger',
            self::HighCancelRate => 'warning',
            self::Other => 'gray',
        };
    }
}
