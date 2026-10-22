<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Họ tên')->required()->maxLength(100),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(191)->unique(ignoreRecord: true),
                TextInput::make('phone')->label('Số điện thoại')->tel()->maxLength(20)->unique(ignoreRecord: true),
                Select::make('level_id')->label('Hạng')->relationship('level', 'name')->placeholder('Tự động theo số đơn'),
                Select::make('role')->label('Vai trò')->options(UserRole::class)->required(),
                Select::make('status')->label('Trạng thái')->options(UserStatus::class)->required(),
            ]);
    }
}
