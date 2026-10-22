<?php

namespace App\Filament\Support;

use App\Enums\OrderStatus;
use App\Enums\OrderStatusSource;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderRewardService;
use App\Services\Orders\OrderStatusService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class OrderActions
{
    /**
     * @return list<Action>
     */
    public static function all(): array
    {
        return [static::changeStatus(), static::assignUser()];
    }

    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('Đổi trạng thái')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->visible(fn (Order $record): bool => $record->status->allowedTransitions() !== [])
            ->schema(fn (Order $record): array => [
                Select::make('status')
                    ->label('Trạng thái mới')
                    ->options(collect($record->status->allowedTransitions())->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->getLabel()]))
                    ->required(),
                Textarea::make('note')->label('Ghi chú')->maxLength(255),
            ])
            ->modalDescription('Duyệt: chuyển tiền sang khả dụng. Huỷ: thu hồi tiền của người mua và F1/F2.')
            ->action(function (Order $record, array $data): void {
                $changed = app(OrderStatusService::class)->transition($record, OrderStatus::from($data['status']), OrderStatusSource::Admin, $data['note'] ?? null);

                $changed
                    ? Notification::make()->title('Đã cập nhật trạng thái đơn.')->success()->send()
                    : Notification::make()->title('Không thể chuyển sang trạng thái này.')->danger()->send();
            });
    }

    /**
     * Gán đơn chưa khớp sub_id cho thành viên (xử lý khiếu nại đơn thiếu) và tính tiền hoàn.
     */
    public static function assignUser(): Action
    {
        return Action::make('assignUser')
            ->label('Gán cho thành viên')
            ->icon(Heroicon::OutlinedUserPlus)
            ->visible(fn (Order $record): bool => $record->user_id === null && $record->status !== OrderStatus::Cancelled)
            ->schema([
                Select::make('user_id')
                    ->label('Thành viên')
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search) => User::query()
                        ->where(fn ($q) => $q->where('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"))
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->email})"]))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->required(),
            ])
            ->action(function (Order $record, array $data): void {
                DB::transaction(function () use ($record, $data): void {
                    $record->update(['user_id' => $data['user_id']]);
                    $record->statusHistories()->create([
                        'from_status' => $record->status->value,
                        'to_status' => $record->status->value,
                        'source' => OrderStatusSource::Admin,
                        'note' => 'Gán đơn cho thành viên #'.$data['user_id'].' bởi '.auth()->user()->name,
                    ]);

                    $rewards = app(OrderRewardService::class);
                    $rewards->createRewards($record->fresh());

                    if (in_array($record->status, [OrderStatus::Approved, OrderStatus::Paid], true)) {
                        $rewards->release($record);
                    }
                });

                Notification::make()->title('Đã gán đơn và tính tiền hoàn.')->success()->send();
            });
    }
}
