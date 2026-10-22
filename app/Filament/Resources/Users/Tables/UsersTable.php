<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Support\UserActions;
use App\Models\User;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('wallet', 'level', 'referrer')->withCount(['referrals', 'orders']))
            ->columns([
                TextColumn::make('name')->label('Họ tên')->searchable()->description(fn (User $record) => $record->email),
                TextColumn::make('phone')->label('SĐT')->searchable()->placeholder('-'),
                TextColumn::make('referral_code')->label('Mã mời')->searchable()->copyable()->fontFamily('mono'),
                TextColumn::make('referrer.name')->label('Người mời')->placeholder('-')->toggleable(),
                TextColumn::make('level.name')->label('Hạng')->placeholder('-')->toggleable(),
                TextColumn::make('wallet.available_balance')->label('Khả dụng')->formatStateUsing(fn ($state) => vnd($state))->alignEnd(),
                TextColumn::make('wallet.pending_balance')->label('Chờ duyệt')->formatStateUsing(fn ($state) => vnd($state))->alignEnd()->toggleable(),
                TextColumn::make('orders_count')->label('Đơn')->alignEnd()->sortable(),
                TextColumn::make('referrals_count')->label('F1')->alignEnd()->sortable(),
                TextColumn::make('role')->label('Vai trò')->badge()->toggleable(),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('created_at')->label('Ngày tham gia')->dateTime('d/m/Y')->sortable(),
                TextColumn::make('last_login_ip')->label('IP đăng nhập')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(UserStatus::class),
                SelectFilter::make('role')->label('Vai trò')->options(UserRole::class),
                SelectFilter::make('level_id')->label('Hạng')->relationship('level', 'name'),
                Filter::make('flagged')->label('Có cờ gian lận đang mở')->query(fn (Builder $query) => $query->whereHas('fraudFlags', fn ($f) => $f->where('status', 'open'))),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make(UserActions::all()),
            ]);
    }
}
