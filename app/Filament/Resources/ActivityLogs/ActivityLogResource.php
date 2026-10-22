<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Support\NavigationGroup;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::System;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'hoạt động';

    protected static ?string $pluralModelLabel = 'Nhật ký hoạt động';

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
                TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i:s'),
                TextColumn::make('user.name')->label('Thành viên')->searchable()->placeholder('Khách'),
                TextColumn::make('action')->label('Hành động')->formatStateUsing(fn (string $state) => __('activity.'.$state))->badge()->color('gray'),
                TextColumn::make('ip')->label('IP')->searchable()->fontFamily('mono'),
                TextColumn::make('device_hash')->label('Thiết bị')->searchable()->limit(12)->fontFamily('mono')->tooltip(fn (ActivityLog $record) => $record->device_hash),
                TextColumn::make('user_agent')->label('Trình duyệt')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')->label('Hành động')->options(fn () => collect(trans('activity'))->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
