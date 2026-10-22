<?php

namespace App\Filament\Pages;

use App\Filament\Support\NavigationGroup;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected string $view = 'filament.pages.manage-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Configuration;

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Cài đặt chung';

    protected static ?string $title = 'Cài đặt chung';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(Settings $settings): void
    {
        $this->form->fill(collect(array_keys(config('cashback.settings')))
            ->mapWithKeys(fn (string $key) => [$key => $settings->get($key)])
            ->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Thông tin website')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')->label('Tên website')->required()->maxLength(100),
                        TextInput::make('support_hotline')->label('Hotline')->maxLength(20),
                        TextInput::make('support_zalo')->label('Link Zalo hỗ trợ')->url(),
                        TextInput::make('support_facebook')->label('Link Fanpage')->url(),
                    ]),
                Section::make('Rút tiền')
                    ->columns(4)
                    ->schema([
                        TextInput::make('withdraw_min')->label('Tối thiểu')->integer()->minValue(0)->suffix('đ')->required(),
                        TextInput::make('withdraw_max')->label('Tối đa mỗi lần (0 = không giới hạn)')->integer()->minValue(0)->suffix('đ')->required(),
                        TextInput::make('withdraw_fee')->label('Phí mỗi lần')->integer()->minValue(0)->suffix('đ')->required(),
                        TextInput::make('withdraw_max_pending')->label('Số yêu cầu chờ tối đa')->integer()->minValue(1)->required(),
                    ]),
                Section::make('Điểm danh')
                    ->schema([
                        TextInput::make('checkin_rewards')
                            ->label('Thưởng theo ngày trong chuỗi (đ, cách nhau dấu phẩy)')
                            ->helperText('Ví dụ 100,100,200,200,300,300,500: ngày 1 thưởng 100đ... hết chu kỳ lặp lại từ đầu.')
                            ->regex('/^\d+(,\d+)*$/')
                            ->required(),
                    ]),
                Section::make('Chống gian lận')
                    ->columns(2)
                    ->schema([
                        TextInput::make('fraud_high_cancel_rate')->label('Gắn cờ khi tỷ lệ huỷ từ')->integer()->minValue(1)->maxValue(100)->suffix('%')->required(),
                        TextInput::make('fraud_high_cancel_min_orders')->label('Chỉ xét khi có tối thiểu')->integer()->minValue(1)->suffix('đơn')->required(),
                    ]),
            ]);
    }

    public function save(Settings $settings): void
    {
        foreach ($this->form->getState() as $key => $value) {
            $settings->set($key, $value);
        }

        Notification::make()->title('Đã lưu cài đặt.')->success()->send();
    }
}
