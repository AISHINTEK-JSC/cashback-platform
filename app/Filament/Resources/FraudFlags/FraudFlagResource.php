<?php

namespace App\Filament\Resources\FraudFlags;

use App\Enums\FraudFlagStatus;
use App\Enums\FraudReason;
use App\Enums\UserStatus;
use App\Filament\Resources\FraudFlags\Pages\ListFraudFlags;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\NavigationGroup;
use App\Models\FraudFlag;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class FraudFlagResource extends Resource
{
    protected static ?string $model = FraudFlag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Operations;

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'cờ gian lận';

    protected static ?string $pluralModelLabel = 'Chống gian lận';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationBadge(): ?string
    {
        $count = FraudFlag::query()->where('status', FraudFlagStatus::Open)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('score', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Thành viên')->searchable()->description(fn (FraudFlag $record) => $record->user?->email)
                    ->url(fn (FraudFlag $record) => UserResource::getUrl('view', ['record' => $record->user_id])),
                TextColumn::make('reason')->label('Lý do')->badge(),
                TextColumn::make('score')->label('Điểm')->sortable()->color(fn (int $state) => $state >= 80 ? 'danger' : ($state >= 50 ? 'warning' : 'gray')),
                TextColumn::make('detail')->label('Chi tiết')->formatStateUsing(fn ($state) => collect($state)->map(fn ($v, $k) => $k.': '.(is_array($v) ? implode(', ', $v) : $v))->implode(' | '))->wrap()->limit(120),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('reviewer.name')->label('Người xem xét')->placeholder('-')->toggleable(),
                TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(FraudFlagStatus::class)->default(FraudFlagStatus::Open->value),
                SelectFilter::make('reason')->label('Lý do')->options(FraudReason::class),
            ])
            ->recordActions([
                Action::make('ignore')
                    ->label('Bỏ qua')
                    ->color('gray')
                    ->icon(Heroicon::OutlinedEyeSlash)
                    ->visible(fn (FraudFlag $record) => $record->status === FraudFlagStatus::Open)
                    ->action(fn (FraudFlag $record) => $record->update(['status' => FraudFlagStatus::Ignored, 'reviewed_by' => auth()->id()])),
                Action::make('confirm')
                    ->label('Xác nhận gian lận')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->visible(fn (FraudFlag $record) => $record->status === FraudFlagStatus::Open)
                    ->modalDescription('Thành viên bị chặn rút tiền khi có cờ đã xác nhận.')
                    ->schema([Toggle::make('lock_user')->label('Khoá luôn tài khoản')->default(false)])
                    ->action(function (FraudFlag $record, array $data): void {
                        $record->update(['status' => FraudFlagStatus::Confirmed, 'reviewed_by' => auth()->id()]);

                        if ($data['lock_user'] ?? false) {
                            $record->user->update(['status' => UserStatus::Locked]);
                        }

                        Notification::make()->title('Đã xác nhận gian lận.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFraudFlags::route('/'),
        ];
    }
}
