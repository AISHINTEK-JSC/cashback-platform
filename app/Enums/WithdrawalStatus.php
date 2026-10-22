<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WithdrawalStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Processing = 'processing';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Chờ duyệt',
            self::Approved => 'Đã duyệt',
            self::Processing => 'Đang chuyển khoản',
            self::Paid => 'Đã thanh toán',
            self::Rejected => 'Từ chối',
            self::Cancelled => 'Đã huỷ',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Processing => 'info',
            self::Paid => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
