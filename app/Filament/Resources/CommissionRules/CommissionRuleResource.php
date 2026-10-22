<?php

namespace App\Filament\Resources\CommissionRules;

use App\Filament\Resources\CommissionRules\Pages\ManageCommissionRules;
use App\Filament\Support\NavigationGroup;
use App\Models\CommissionRule;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CommissionRuleResource extends Resource
{
    protected static ?string $model = CommissionRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Configuration;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'quy tắc hoa hồng';

    protected static ?string $pluralModelLabel = 'Quy tắc hoa hồng';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $rateField = fn (string $name, string $label) => TextInput::make($name)->label($label)->numeric()->minValue(0)->maxValue(100)->suffix('%')->required()->live(onBlur: true);

        return $schema
            ->columns(3)
            ->components([
                Select::make('platform_id')->label('Sàn')->relationship('platform', 'name')->required(),
                TextInput::make('category_code')->label('Mã ngành hàng')->helperText('Bỏ trống = áp dụng mặc định cho sàn')->maxLength(50),
                DateTimePicker::make('effective_from')->label('Hiệu lực từ')->default(now())->required(),
                $rateField('user_rate', '% cho người mua'),
                $rateField('f1_rate', '% cho F1'),
                $rateField('f2_rate', '% cho F2')
                    ->rule(fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get): void {
                        if ((float) $get('user_rate') + (float) $get('f1_rate') + (float) $value > 100) {
                            $fail('Tổng % người mua + F1 + F2 không được vượt 100%.');
                        }
                    }),
                DateTimePicker::make('effective_to')->label('Hiệu lực đến')->helperText('Bỏ trống = đang áp dụng. Muốn đổi tỷ lệ: đặt ngày kết thúc cho quy tắc cũ rồi tạo quy tắc mới, đơn cũ giữ nguyên tỷ lệ.')->after('effective_from')->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('effective_from', 'desc')
            ->columns([
                TextColumn::make('platform.name')->label('Sàn')->badge()->color('gray'),
                TextColumn::make('category_code')->label('Ngành hàng')->placeholder('Mặc định'),
                TextColumn::make('user_rate')->label('Người mua')->suffix('%'),
                TextColumn::make('f1_rate')->label('F1')->suffix('%'),
                TextColumn::make('f2_rate')->label('F2')->suffix('%'),
                TextColumn::make('margin')->label('Giữ lại')->state(fn (CommissionRule $record) => round(100 - $record->user_rate - $record->f1_rate - $record->f2_rate, 2))->suffix('%')->color('success'),
                TextColumn::make('effective_from')->label('Từ')->dateTime('d/m/Y H:i'),
                TextColumn::make('effective_to')->label('Đến')->dateTime('d/m/Y H:i')->placeholder('Đang áp dụng'),
            ])
            ->filters([
                SelectFilter::make('platform_id')->label('Sàn')->relationship('platform', 'name'),
                TernaryFilter::make('active')->label('Đang áp dụng')->queries(
                    true: fn (Builder $query) => $query->effectiveAt(now()),
                    false: fn (Builder $query) => $query->where(fn (Builder $w) => $w->where('effective_from', '>', now())->orWhere('effective_to', '<=', now())),
                ),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCommissionRules::route('/'),
        ];
    }
}
