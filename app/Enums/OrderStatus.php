<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Approved = 'approved';
    case Cancelled = 'cancelled';
    case Paid = 'paid';

    /**
     * Các trạng thái có thể chuyển tới. Đơn chỉ đi tiến, trừ huỷ (kể cả sau khi duyệt - sàn thu hồi hoa hồng).
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Approved, self::Cancelled],
            self::Confirmed => [self::Approved, self::Cancelled],
            self::Approved => [self::Paid, self::Cancelled],
            self::Cancelled, self::Paid => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Đã ghi nhận',
            self::Confirmed => 'Sàn đã xác nhận',
            self::Approved => 'Có thể rút',
            self::Cancelled => 'Đã huỷ',
            self::Paid => 'Đã đối soát',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Approved => 'success',
            self::Cancelled => 'danger',
            self::Paid => 'gray',
        };
    }
}
