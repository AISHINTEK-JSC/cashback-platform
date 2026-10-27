<?php

namespace App\Filament\Resources\SupportTickets;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Support\NavigationGroup;
use App\Models\SupportTicket;
use App\Models\TrackingLink;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use UnitEnum;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Operations;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'yêu cầu hỗ trợ';

    protected static ?string $pluralModelLabel = 'Hỗ trợ / khiếu nại';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationBadge(): ?string
    {
        $count = SupportTicket::query()->where('status', TicketStatus::Open)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(4)
                ->schema([
                    TextEntry::make('user.name')->label('Thành viên')->helperText(fn (SupportTicket $record) => $record->user?->email),
                    TextEntry::make('type')->label('Loại')->badge(),
                    TextEntry::make('status')->label('Trạng thái')->badge(),
                    TextEntry::make('assignee.name')->label('Phụ trách')->placeholder('Chưa giao'),
                    TextEntry::make('platform.name')->label('Sàn')->placeholder('-'),
                    TextEntry::make('platform_order_id')->label('Mã đơn khai báo')->placeholder('-')->copyable(),
                    TextEntry::make('created_at')->label('Tạo lúc')->dateTime('d/m/Y H:i'),
                ]),
            Section::make('Khiếu nại thiếu đơn')
                ->description('Hệ thống tự đối chiếu lúc thành viên gửi khiếu nại.')
                ->visible(fn (SupportTicket $record) => $record->type === TicketType::MissingOrder && filled($record->details))
                ->columns(4)
                ->schema([
                    TextEntry::make('details.purchased_at')->label('Ngày đặt hàng')->date('d/m/Y'),
                    TextEntry::make('details.order_amount')->label('Giá trị khai báo')->formatStateUsing(fn ($state) => vnd((int) $state))->placeholder('-'),
                    TextEntry::make('claim_check')
                        ->label('Kết quả đối chiếu')
                        ->badge()
                        ->state(fn (SupportTicket $record) => match ($record->details['check']['result'] ?? null) {
                            'unmatched' => 'Có đơn, chưa gán thành viên',
                            'other_user' => 'Đơn thuộc thành viên khác',
                            default => 'Không tìm thấy đơn',
                        })
                        ->color(fn (SupportTicket $record) => match ($record->details['check']['result'] ?? null) {
                            'unmatched' => 'warning',
                            'other_user' => 'danger',
                            default => 'gray',
                        })
                        ->url(fn (SupportTicket $record) => filled($record->details['check']['order_id'] ?? null) ? OrderResource::getUrl('view', ['record' => $record->details['check']['order_id']]) : null),
                    TextEntry::make('claim_selected_link')
                        ->label('Link thành viên chọn')
                        ->state(fn (SupportTicket $record) => ($link = static::claimLinks($record, [$record->details['tracking_link_id'] ?? null])->first())
                            ? $link->sub_id.' · '.$link->created_at->format('d/m H:i').' · '.$link->click_count.' lượt mở'
                            : null)
                        ->copyable()
                        ->placeholder('Không chọn'),
                    TextEntry::make('claim_related_links')
                        ->label('Link trên sàn này trong 7 ngày trước ngày mua (đối chiếu sub_id với báo cáo của sàn)')
                        ->state(fn (SupportTicket $record) => static::claimLinks($record, $record->details['check']['related_link_ids'] ?? [])
                            ->map(fn (TrackingLink $link) => $link->sub_id.' · '.$link->created_at->format('d/m H:i').' · '.$link->click_count.' lượt mở · '.Str::limit($link->product_name ?? $link->original_url, 40))
                            ->all())
                        ->listWithLineBreaks()
                        ->placeholder('Không có link nào, có thể thành viên không mua qua link hoàn tiền')
                        ->columnSpanFull(),
                ]),
            Section::make('Hội thoại')
                ->schema([
                    RepeatableEntry::make('messages')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('user.name')->label('Người gửi')->weight('bold')
                                ->helperText(fn ($record) => $record->created_at?->format('d/m/Y H:i')),
                            TextEntry::make('message')->hiddenLabel()->prose(),
                            ImageEntry::make('attachments')->hiddenLabel()->disk('uploads')->visibility('public')->visible(fn ($record) => ! empty($record->attachments)),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('subject')->label('Tiêu đề')->searchable()->limit(50),
                TextColumn::make('user.name')->label('Thành viên')->searchable(),
                TextColumn::make('type')->label('Loại')->badge(),
                TextColumn::make('platform_order_id')->label('Mã đơn')->searchable()->placeholder('-'),
                TextColumn::make('status')->label('Trạng thái')->badge(),
                TextColumn::make('assignee.name')->label('Phụ trách')->placeholder('-'),
                TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(TicketStatus::class)->default(TicketStatus::Open->value),
                SelectFilter::make('type')->label('Loại')->options(TicketType::class),
            ])
            ->recordActions([ViewAction::make()]);
    }

    /**
     * Link hoàn tiền nêu trong khiếu nại, chỉ lấy link của chính thành viên gửi ticket.
     *
     * @param  array<int, int|null>  $ids
     * @return Collection<int, TrackingLink>
     */
    private static function claimLinks(SupportTicket $ticket, array $ids): Collection
    {
        $ids = array_values(array_filter($ids));

        return $ids === [] ? collect() : TrackingLink::query()->where('user_id', $ticket->user_id)->whereIn('id', $ids)->latest()->get();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'view' => ViewSupportTicket::route('/{record}'),
        ];
    }
}
