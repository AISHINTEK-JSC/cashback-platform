<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Enums\TicketStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Notifications\TicketReplied;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property SupportTicket $record
 */
class ViewSupportTicket extends ViewRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('findOrder')
                ->label('Tìm đơn trên hệ thống')
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->color('gray')
                ->visible(fn (): bool => filled($this->record->platform_order_id))
                ->url(function (): string {
                    $order = Order::query()->where('platform_order_id', $this->record->platform_order_id)->first();

                    return $order
                        ? OrderResource::getUrl('view', ['record' => $order])
                        : OrderResource::getUrl('index', ['tableSearch' => $this->record->platform_order_id]);
                }),
            Action::make('reply')
                ->label('Trả lời')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->schema([Textarea::make('message')->label('Nội dung')->required()->rows(5)])
                ->action(function (array $data): void {
                    $this->record->messages()->create(['user_id' => auth()->id(), 'message' => $data['message']]);
                    $this->record->update(['status' => TicketStatus::Answered, 'assigned_to' => $this->record->assigned_to ?? auth()->id()]);
                    $this->record->user->notify(new TicketReplied($this->record));
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Đã gửi trả lời.')->success()->send();
                }),
            Action::make('close')
                ->label('Đóng')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status !== TicketStatus::Closed)
                ->action(function (): void {
                    $this->record->update(['status' => TicketStatus::Closed]);
                    Notification::make()->title('Đã đóng yêu cầu.')->success()->send();
                }),
        ];
    }
}
