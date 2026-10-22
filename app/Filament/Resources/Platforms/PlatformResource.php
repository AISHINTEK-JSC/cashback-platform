<?php

namespace App\Filament\Resources\Platforms;

use App\Filament\Resources\Platforms\Pages\ManagePlatforms;
use App\Filament\Support\NavigationGroup;
use App\Models\Platform;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PlatformResource extends Resource
{
    protected static ?string $model = Platform::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Configuration;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'sàn liên kết';

    protected static ?string $pluralModelLabel = 'Sàn liên kết';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')->label('Mã')->helperText('shopee, tiktok, lazada - dùng để chọn driver API')->required()->alphaDash()->maxLength(20)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Tên hiển thị')->required()->maxLength(50),
                TextInput::make('lock_days')->label('Số ngày chờ sàn chốt đơn')->integer()->minValue(0)->required(),
                Toggle::make('is_active')->label('Đang bật')->inline(false),
                FileUpload::make('logo')->label('Logo')->image()->disk('public')->directory('platforms'),
                KeyValue::make('api_config')
                    ->label('Cấu hình API (được mã hoá)')
                    ->keyLabel('Khoá')
                    ->valueLabel('Giá trị')
                    ->helperText('Shopee: app_id, secret. Lưu ý chọn driver thật trong biến môi trường AFFILIATE_*_DRIVER.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Sàn'),
                TextColumn::make('code')->label('Mã')->fontFamily('mono'),
                TextColumn::make('driver')->label('Driver')->state(fn (Platform $record) => config("cashback.drivers.{$record->code}", 'fake'))->badge()->color(fn (string $state) => $state === 'fake' ? 'warning' : 'success'),
                TextColumn::make('lock_days')->label('Ngày chờ chốt'),
                IconColumn::make('is_active')->label('Bật')->boolean(),
                TextColumn::make('orders_count')->label('Số đơn')->counts('orders'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlatforms::route('/'),
        ];
    }
}
