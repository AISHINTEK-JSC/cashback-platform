<?php

namespace App\Filament\Support;

use App\Enums\UserStatus;
use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class UserActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [static::lock(), static::unlock(), static::adjustBalance()];
    }

    public static function lock(): Action
    {
        return Action::make('lock')
            ->label('Khoá tài khoản')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (User $record): bool => $record->status !== UserStatus::Locked && $record->isNot(auth()->user()))
            ->action(function (User $record): void {
                $record->update(['status' => UserStatus::Locked]);
                Notification::make()->title('Đã khoá tài khoản.')->success()->send();
            });
    }

    public static function unlock(): Action
    {
        return Action::make('unlock')
            ->label('Mở khoá')
            ->icon(Heroicon::OutlinedLockOpen)
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (User $record): bool => $record->status === UserStatus::Locked)
            ->action(function (User $record): void {
                $record->update(['status' => UserStatus::Active]);
                Notification::make()->title('Đã mở khoá tài khoản.')->success()->send();
            });
    }

    /**
     * Điều chỉnh tay: ghi một bút toán "adjust" có người tạo, không sửa số dư trực tiếp.
     */
    public static function adjustBalance(): Action
    {
        return Action::make('adjustBalance')
            ->label('Điều chỉnh số dư')
            ->icon(Heroicon::OutlinedScale)
            ->color('warning')
            ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
            ->schema([
                Select::make('bucket')->label('Số dư')->options([
                    WalletBucket::Available->value => WalletBucket::Available->getLabel(),
                    WalletBucket::Pending->value => WalletBucket::Pending->getLabel(),
                ])->default(WalletBucket::Available->value)->required(),
                TextInput::make('amount')->label('Số tiền (âm để trừ)')->integer()->required()->notIn([0])->suffix('đ'),
                TextInput::make('description')->label('Nội dung (thành viên nhìn thấy)')->required()->maxLength(255),
            ])
            ->action(function (User $record, array $data): void {
                try {
                    app(WalletService::class)->post(
                        $record,
                        WalletTransactionType::Adjust,
                        WalletBucket::from($data['bucket']),
                        (int) $data['amount'],
                        description: $data['description'],
                        createdBy: auth()->id(),
                    );
                    Notification::make()->title('Đã ghi bút toán điều chỉnh.')->success()->send();
                } catch (BusinessRuleException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
