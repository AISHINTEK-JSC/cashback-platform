<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Nhà mạng nhận nạp tiền điện thoại / phát hành thẻ cào. Người dùng tự chọn vì số chuyển mạng giữ số không đoán được theo đầu số.
 */
enum PhoneCarrier: string implements HasLabel
{
    case Viettel = 'viettel';
    case Vinaphone = 'vinaphone';
    case Mobifone = 'mobifone';
    case Vietnamobile = 'vietnamobile';

    public function getLabel(): string
    {
        return match ($this) {
            self::Viettel => 'Viettel',
            self::Vinaphone => 'Vinaphone',
            self::Mobifone => 'Mobifone',
            self::Vietnamobile => 'Vietnamobile',
        };
    }
}
