<?php

namespace App\Filament\Support;

use App\Enums\WithdrawalStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Các thao tác xử lý yêu cầu rút tiền, dùng chung cho bảng danh sách và trang chi tiết.
 */
class WithdrawalActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [static::approve(), static::processing(), static::markPaid(), static::reject()];
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Duyệt')
            ->icon(Heroicon::OutlinedCheck)
            ->color('info')
            ->requiresConfirmation()
            ->visible(fn (Withdrawal $record): bool => $record->status === WithdrawalStatus::Pending)
            ->action(fn (Withdrawal $record) => static::run(fn (WithdrawalService $s) => $s->approve($record, auth()->user()), 'Đã duyệt yêu cầu.'));
    }

    public static function processing(): Action
    {
        return Action::make('processing')
            ->label('Đang chuyển khoản')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn (Withdrawal $record): bool => in_array($record->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true))
            ->action(fn (Withdrawal $record) => static::run(fn (WithdrawalService $s) => $s->markProcessing($record, auth()->user()), 'Đã chuyển sang đang chuyển khoản.'));
    }

    public static function markPaid(): Action
    {
        return Action::make('markPaid')
            ->label('Đã thanh toán')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->modalDescription(fn (Withdrawal $record): string => 'Chuyển '.vnd($record->net_amount)." tới {$record->bank_name} {$record->account_number} - {$record->account_name}")
            ->schema([
                TextInput::make('bank_txn_ref')->label('Mã giao dịch ngân hàng')->maxLength(100),
            ])
            ->visible(fn (Withdrawal $record): bool => in_array($record->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing], true))
            ->action(fn (Withdrawal $record, array $data) => static::run(fn (WithdrawalService $s) => $s->markPaid($record, auth()->user(), $data['bank_txn_ref'] ?? null), 'Đã xác nhận thanh toán.'));
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Từ chối')
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->schema([
                Textarea::make('reason')->label('Lý do (hiển thị cho thành viên)')->required()->maxLength(255),
            ])
            ->visible(fn (Withdrawal $record): bool => in_array($record->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing], true))
            ->action(fn (Withdrawal $record, array $data) => static::run(fn (WithdrawalService $s) => $s->reject($record, auth()->user(), $data['reason']), 'Đã từ chối và hoàn tiền về ví thành viên.'));
    }

    private static function run(callable $callback, string $successMessage): void
    {
        try {
            $callback(app(WithdrawalService::class));
            Notification::make()->title($successMessage)->success()->send();
        } catch (BusinessRuleException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
