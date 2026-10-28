<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Cách nhận tiền rút: chuyển khoản (ngân hàng hoặc ví điện tử trong danh sách VietQR), nạp thẳng vào số điện thoại, hoặc nhận mã thẻ cào.
 */
enum WithdrawalMethod: string implements HasColor, HasLabel
{
    case Bank = 'bank';
    case PhoneTopup = 'phone_topup';
    case PhoneCard = 'phone_card';

    /**
     * Rút theo mệnh giá cố định của nhà mạng, không thu phí và không áp mức rút tối thiểu/tối đa của chuyển khoản.
     */
    public function isPhone(): bool
    {
        return $this !== self::Bank;
    }

    /**
     * Admin bật/tắt từng cách rút bằng điện thoại ở trang Cài đặt chung.
     */
    public function isEnabled(): bool
    {
        return match ($this) {
            self::Bank => true,
            self::PhoneTopup => (bool) setting('phone_topup_enabled'),
            self::PhoneCard => (bool) setting('phone_card_enabled'),
        };
    }

    /**
     * Nhãn ngắn cho tab chọn cách nhận tiền ở form rút.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Bank => 'Ngân hàng / ví',
            self::PhoneTopup => 'Nạp điện thoại',
            self::PhoneCard => 'Thẻ cào',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Bank => 'Ngân hàng / ví điện tử',
            self::PhoneTopup => 'Nạp tiền điện thoại',
            self::PhoneCard => 'Thẻ cào điện thoại',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Bank => 'gray',
            self::PhoneTopup => 'info',
            self::PhoneCard => 'primary',
        };
    }
}
