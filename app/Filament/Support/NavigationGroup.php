<?php

namespace App\Filament\Support;

use Filament\Support\Contracts\HasLabel;

/**
 * Nhóm menu của trang quản trị, thứ tự khai báo = thứ tự hiển thị.
 */
enum NavigationGroup: string implements HasLabel
{
    case Operations = 'operations';
    case Members = 'members';
    case Content = 'content';
    case Configuration = 'configuration';
    case System = 'system';

    public function getLabel(): string
    {
        return match ($this) {
            self::Operations => 'Vận hành',
            self::Members => 'Thành viên',
            self::Content => 'Nội dung',
            self::Configuration => 'Cấu hình',
            self::System => 'Hệ thống',
        };
    }
}
