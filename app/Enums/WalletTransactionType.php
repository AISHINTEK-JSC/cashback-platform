<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WalletTransactionType: string implements HasColor, HasLabel
{
    case Cashback = 'cashback';
    case Referral = 'referral';
    case Checkin = 'checkin';
    case Bonus = 'bonus';
    case Withdraw = 'withdraw';
    case WithdrawRefund = 'withdraw_refund';
    case Adjust = 'adjust';
    case Reversal = 'reversal';
    case Release = 'release';
    case Payout = 'payout';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cashback => 'Hoàn tiền',
            self::Referral => 'Hoa hồng giới thiệu',
            self::Checkin => 'Điểm danh',
            self::Bonus => 'Thưởng',
            self::Withdraw => 'Rút tiền',
            self::WithdrawRefund => 'Hoàn lại tiền rút',
            self::Adjust => 'Điều chỉnh',
            self::Reversal => 'Thu hồi',
            self::Release => 'Chuyển sang khả dụng',
            self::Payout => 'Đã chuyển khoản',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Cashback => 'success',
            self::Referral => 'info',
            self::Checkin => 'primary',
            self::Bonus => 'primary',
            self::Withdraw => 'warning',
            self::WithdrawRefund => 'gray',
            self::Adjust => 'gray',
            self::Reversal => 'danger',
            self::Release => 'gray',
            self::Payout => 'gray',
        };
    }
}
