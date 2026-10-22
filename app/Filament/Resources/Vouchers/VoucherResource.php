<?php

namespace App\Filament\Resources\Vouchers;

use App\Enums\DiscountType;
use App\Filament\Resources\Vouchers\Pages\ManageVouchers;
use App\Filament\Support\NavigationGroup;
use App\Models\Voucher;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Content;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'mã giảm giá';

    protected static ?string $pluralModelLabel = 'Mã giảm giá';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('platform_id')->label('Sàn')->relationship('platform', 'name')->required(),
                TextInput::make('code')->label('Mã')->required()->maxLength(50),
                TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255)->columnSpanFull(),
                Select::make('discount_type')->label('Loại giảm')->options(DiscountType::class)->required(),
                TextInput::make('discount_value')->label('Giá trị (% hoặc đ)')->integer()->minValue(0)->required(),
                TextInput::make('min_order')->label('Đơn tối thiểu')->integer()->minValue(0)->default(0)->suffix('đ'),
                TextInput::make('max_discount')->label('Giảm tối đa')->integer()->minValue(0)->suffix('đ'),
                TextInput::make('url')->label('Link lấy mã')->url()->maxLength(500)->columnSpanFull(),
                DateTimePicker::make('starts_at')->label('Bắt đầu'),
                DateTimePicker::make('expires_at')->label('Hết hạn'),
                TextInput::make('sort_order')->label('Thứ tự')->integer()->default(0),
                Toggle::make('is_active')->label('Hiển thị')->default(true)->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('platform.name')->label('Sàn')->badge()->color('gray'),
                TextColumn::make('code')->label('Mã')->searchable()->fontFamily('mono')->copyable(),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->limit(40),
                TextColumn::make('discount_type')->label('Loại')->badge(),
                TextColumn::make('expires_at')->label('Hết hạn')->dateTime('d/m/Y H:i')->sortable()->color(fn (Voucher $record) => $record->expires_at?->isPast() ? 'danger' : null),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
                ToggleColumn::make('is_active')->label('Hiển thị'),
            ])
            ->filters([
                SelectFilter::make('platform_id')->label('Sàn')->relationship('platform', 'name'),
            ])
            ->recordActions([EditAction::make(), ReplicateAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVouchers::route('/'),
        ];
    }
}
