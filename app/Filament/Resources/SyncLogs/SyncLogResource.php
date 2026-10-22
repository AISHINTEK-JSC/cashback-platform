<?php

namespace App\Filament\Resources\SyncLogs;

use App\Enums\SyncStatus;
use App\Filament\Resources\SyncLogs\Pages\ListSyncLogs;
use App\Filament\Support\NavigationGroup;
use App\Models\SyncLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SyncLogResource extends Resource
{
    protected static ?string $model = SyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'lượt đồng bộ';

    protected static ?string $pluralModelLabel = 'Nhật ký đồng bộ';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('platform.name')->label('Sàn'),
                TextColumn::make('window_from')->label('Từ')->dateTime('d/m H:i'),
                TextColumn::make('window_to')->label('Đến')->dateTime('d/m H:i'),
                TextColumn::make('fetched')->label('Lấy về'),
                TextColumn::make('inserted')->label('Mới'),
                TextColumn::make('updated')->label('Cập nhật'),
                TextColumn::make('status')->label('Kết quả')->badge(),
                TextColumn::make('error')->label('Lỗi')->limit(60)->placeholder('-')->tooltip(fn (SyncLog $record) => $record->error),
                TextColumn::make('started_at')->label('Bắt đầu')->dateTime('d/m/Y H:i:s'),
                TextColumn::make('duration')->label('Thời gian chạy')->state(fn (SyncLog $record) => $record->finished_at ? $record->started_at->diffInSeconds($record->finished_at).'s' : '-'),
            ])
            ->filters([
                SelectFilter::make('platform_id')->label('Sàn')->relationship('platform', 'name'),
                SelectFilter::make('status')->label('Kết quả')->options(SyncStatus::class),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSyncLogs::route('/'),
        ];
    }
}
