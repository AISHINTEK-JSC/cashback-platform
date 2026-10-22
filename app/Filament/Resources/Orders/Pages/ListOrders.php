<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\SyncStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Platform;
use App\Services\Orders\OrderSyncService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncNow')
                ->label('Đồng bộ ngay')
                ->icon(Heroicon::OutlinedArrowPath)
                ->schema([
                    Select::make('platform_id')->label('Sàn')->options(Platform::query()->active()->pluck('name', 'id'))->required(),
                    Select::make('days')->label('Khoảng thời gian')->options([1 => '24 giờ qua', 7 => '7 ngày qua', 30 => '30 ngày qua'])->default(1)->required(),
                ])
                ->action(function (array $data): void {
                    $log = app(OrderSyncService::class)->sync(Platform::findOrFail($data['platform_id']), now()->subDays((int) $data['days']), now());

                    $log->status === SyncStatus::Success
                        ? Notification::make()->title("Đồng bộ xong: {$log->fetched} đơn, {$log->inserted} mới, {$log->updated} cập nhật.")->success()->send()
                        : Notification::make()->title('Đồng bộ lỗi')->body($log->error)->danger()->send();
                }),
        ];
    }
}
