<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Thưởng một lần cho thành viên mới, mỗi loại chỉ nhận một lần (unique user_id + type trong user_bonuses).
 */
enum BonusType: string implements HasLabel
{
    case FirstOrder = 'first_order';
    case Onboarding = 'onboarding';

    public function getLabel(): string
    {
        return match ($this) {
            self::FirstOrder => 'Thưởng đơn đầu tiên',
            self::Onboarding => 'Thưởng hoàn thành hướng dẫn',
        };
    }
}
