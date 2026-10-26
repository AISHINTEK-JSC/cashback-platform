<?php

namespace App\Filament\Resources\SupportTickets;

use App\Enums\TicketStatus;
use App\Enums\TicketType;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Support\NavigationGroup;
use App\Models\SupportTicket;
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

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'view' => ViewSupportTicket::route('/{record}'),
        ];
    }
}
