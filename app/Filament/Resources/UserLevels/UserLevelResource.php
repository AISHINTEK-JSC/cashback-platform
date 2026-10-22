<?php

namespace App\Filament\Resources\UserLevels;

use App\Filament\Resources\UserLevels\Pages\ManageUserLevels;
use App\Filament\Support\NavigationGroup;
use App\Models\UserLevel;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserLevelResource extends Resource
{
    protected static ?string $model = UserLevel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Members;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'hạng thành viên';

    protected static ?string $pluralModelLabel = 'Hạng thành viên';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Tên hạng')->required()->maxLength(50),
            TextInput::make('min_approved_orders')->label('Số đơn được duyệt tối thiểu')->integer()->minValue(0)->required(),
            TextInput::make('bonus_rate')->label('% cộng thêm trên phần hoàn của thành viên')->numeric()->minValue(0)->maxValue(100)->suffix('%')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('min_approved_orders')
            ->columns([
                TextColumn::make('name')->label('Tên hạng'),
                TextColumn::make('min_approved_orders')->label('Từ số đơn'),
                TextColumn::make('bonus_rate')->label('Cộng thêm')->suffix('%'),
                TextColumn::make('users_count')->label('Số thành viên')->counts('users'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUserLevels::route('/'),
        ];
    }
}
