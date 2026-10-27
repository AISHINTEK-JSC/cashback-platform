<?php

namespace App\Filament\Resources\Platforms;

use App\Filament\Resources\Platforms\Pages\ManagePlatforms;
use App\Filament\Support\NavigationGroup;
use App\Models\Platform;
use App\Services\Affiliate\AffiliateManager;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
                TextInput::make('code')->label('Mã')->helperText('shopee, tiktok, lazada, adidas, apple - dùng để chọn driver API')->required()->alphaDash()->maxLength(20)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Tên hiển thị')->required()->maxLength(50),
                Select::make('driver')
                    ->label('Driver API')
                    ->options(AffiliateManager::DRIVER_LABELS)
                    ->placeholder('Theo biến môi trường AFFILIATE_*_DRIVER')
                    ->helperText('Chọn "Giả lập" khi chưa có campaign: link và đơn sinh ra là giả.'),
                TextInput::make('lock_days')->label('Số ngày chờ sàn chốt đơn')->integer()->minValue(0)->required(),
                TextInput::make('max_commission_rate')
                    ->label('Hoa hồng tối đa sàn trả')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%')
                    ->helperText('Trang chủ hiển thị "Hoàn đến" = mức này × % chia cho người mua. Để trống thì không hiện.'),
                Toggle::make('is_active')->label('Đang bật')->inline(false),
                Toggle::make('is_coming_soon')->label('Hiển thị "Sắp ra mắt" khi chưa bật')->inline(false),
                FileUpload::make('logo')->label('Logo')->helperText('Để trống thì dùng logo mặc định trong public/images/platforms (nếu có).')->image()->disk('uploads')->visibility('public')->directory('platforms'),
                RichEditor::make('landing_content')
                    ->label('Nội dung trang Hoàn tiền của sàn')
                    ->helperText(fn (?Platform $record) => 'Hiện ở trang '.($record ? route('platforms.show', $record) : '/hoan-tien/<mã sàn>').'. Viết riêng cho sàn này (mẹo mua sắm, ngành hàng hoàn cao, lưu ý khi mua) để trang có nội dung độc nhất, giúp lên Google tốt hơn.')
                    ->toolbarButtons(['h2', 'h3', 'bold', 'italic', 'link', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo'])
                    ->columnSpanFull(),
                KeyValue::make('api_config')
                    ->label('Cấu hình API (được mã hoá)')
                    ->keyLabel('Khoá')
                    ->valueLabel('Giá trị')
                    ->helperText('Shopee: app_id, secret. AccessTrade (tuỳ chọn): campaign_id, merchant để ghi đè mặc định. API key AccessTrade nhập ở Cài đặt chung.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Sàn'),
                TextColumn::make('code')->label('Mã')->fontFamily('mono'),
                TextColumn::make('driver')->label('Driver')->state(fn (Platform $record) => app(AffiliateManager::class)->driverName($record))->badge()->color(fn (string $state) => $state === 'fake' ? 'warning' : 'success'),
                TextColumn::make('lock_days')->label('Ngày chờ chốt'),
                IconColumn::make('is_active')->label('Bật')->boolean(),
                IconColumn::make('is_coming_soon')->label('Sắp ra mắt')->boolean(),
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
