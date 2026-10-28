<?php

namespace App\Filament\Pages;

use App\Filament\Support\NavigationGroup;
use App\Support\Settings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
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
                        TextInput::make('support_email')->label('Email liên hệ')->email()->maxLength(191),
                        TextInput::make('operator_name')
                            ->label('Đơn vị/cá nhân vận hành')
                            ->helperText('Hiển thị trong Điều khoản và Chính sách bảo mật, bỏ trống thì ghi "một cá nhân".')
                            ->maxLength(150),
                        TextInput::make('operator_address')->label('Địa chỉ vận hành')->maxLength(255),
                        Toggle::make('transparency_page')
                            ->label('Bật trang Minh bạch chi trả (/minh-bach)')
                            ->helperText('Công khai tổng tiền đã chi trả, thời gian xử lý rút tiền và các lần chi trả gần đây (tên đã che). Có thể tắt khi số liệu còn ít.')
                            ->inline(false),
                    ]),
                Section::make('SEO & thống kê')
                    ->description('Gắn Google Analytics 4 và xác minh Google Search Console. GA chỉ chạy trên môi trường production.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('google_analytics_id')
                            ->label('Mã đo lường GA4')
                            ->placeholder('G-XXXXXXXXXX')
                            ->regex('/^G-[A-Z0-9]+$/')
                            ->maxLength(30),
                        TextInput::make('google_site_verification')
                            ->label('Mã xác minh Search Console')
                            ->helperText('Chọn phương thức "Thẻ HTML", chỉ dán phần trong content="...".')
                            ->regex('/^[A-Za-z0-9_-]+$/')
                            ->maxLength(100),
                    ]),
                Section::make('Rút tiền')
                    ->columns(4)
                    ->schema([
                        TextInput::make('withdraw_min')->label('Tối thiểu')->integer()->minValue(0)->suffix('đ')->required(),
                        TextInput::make('withdraw_max')->label('Tối đa mỗi lần (0 = không giới hạn)')->integer()->minValue(0)->suffix('đ')->required(),
                        TextInput::make('withdraw_fee')->label('Phí mỗi lần')->integer()->minValue(0)->suffix('đ')->required(),
                        TextInput::make('withdraw_max_pending')->label('Số yêu cầu chờ tối đa')->integer()->minValue(1)->required(),
                        TextInput::make('withdraw_new_bank_hold_hours')
                            ->label('Khoá rút về tài khoản mới thêm')
                            ->helperText('Số giờ phải chờ sau khi thành viên thêm tài khoản ngân hàng, 0 = tắt.')
                            ->integer()->minValue(0)->maxValue(168)->suffix('giờ')->required(),
                    ]),
                Section::make('Rút bằng nạp điện thoại / thẻ cào')
                    ->description('Thành viên chọn mệnh giá, admin tự nạp hoặc mua thẻ (VD trong app ngân hàng) rồi xác nhận. Không thu phí, không áp mức rút tối thiểu/tối đa ở trên.')
                    ->columns(3)
                    ->schema([
                        Toggle::make('phone_topup_enabled')->label('Cho rút bằng nạp tiền điện thoại')->inline(false),
                        Toggle::make('phone_card_enabled')->label('Cho rút bằng thẻ cào')->inline(false),
                        TextInput::make('phone_withdraw_amounts')
                            ->label('Mệnh giá (đ, cách nhau dấu phẩy)')
                            ->helperText('Ví dụ 10000,20000,50000,100000,200000,500000')
                            ->regex('/^\d+(,\d+)*$/')
                            ->required(),
                    ]),
                Section::make('Điểm danh')
                    ->schema([
                        TextInput::make('checkin_rewards')
                            ->label('Thưởng theo ngày trong chuỗi (đ, cách nhau dấu phẩy)')
                            ->helperText('Ví dụ 100,100,200,200,300,300,500: ngày 1 thưởng 100đ... hết chu kỳ lặp lại từ đầu.')
                            ->regex('/^\d+(,\d+)*$/')
                            ->required(),
                    ]),
                Section::make('Thưởng thành viên mới')
                    ->description('Mỗi thành viên chỉ nhận mỗi loại thưởng một lần. Đặt 0 để tắt.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('first_order_bonus')
                            ->label('Thưởng đơn đầu tiên')
                            ->helperText('Cộng vào số dư khả dụng khi đơn đầu tiên được duyệt, thu hồi nếu đơn đó bị huỷ sau này.')
                            ->integer()->minValue(0)->suffix('đ')->required(),
                        TextInput::make('first_order_bonus_min_order')
                            ->label('Giá trị đơn tối thiểu để nhận')
                            ->integer()->minValue(0)->suffix('đ')->required(),
                        TextInput::make('onboarding_bonus')
                            ->label('Thưởng hoàn thành hướng dẫn')
                            ->helperText('Hoàn thành danh sách việc cần làm ở trang tổng quan rồi bấm Nhận thưởng.')
                            ->integer()->minValue(0)->suffix('đ')->required(),
                    ]),
                Section::make('Bot Telegram')
                    ->columns(2)
                    ->schema([
                        TextInput::make('telegram_chat_rate_limit')
                            ->label('Giới hạn tin nhắn mỗi chat')
                            ->helperText('Số tin tối đa bot xử lý cho một người trong 1 phút, vượt quá thì bỏ qua và nhắc chậm lại. 0 = không giới hạn.')
                            ->integer()->minValue(0)->maxValue(600)->suffix('tin/phút')->required(),
                        Toggle::make('telegram_dashboard_prompt')
                            ->label('Gợi ý liên kết Telegram ở trang tổng quan')
                            ->helperText('Tắt thì chỉ còn mục Telegram trong trang Hồ sơ của thành viên.')
                            ->inline(false),
                    ]),
                Section::make('Tích hợp API')
                    ->description('Lưu mã hoá trong hệ thống, được ưu tiên hơn file .env. Để trống ô nào thì giữ nguyên giá trị cũ của ô đó.')
                    ->columns(2)
                    ->schema([
                        $this->secretInput('accesstrade_api_key', 'API key AccessTrade')->columnSpanFull(),
                        $this->secretInput('google_client_id', 'Google Client ID'),
                        $this->secretInput('google_client_secret', 'Google Client Secret'),
                        $this->secretInput('facebook_client_id', 'Facebook App ID'),
                        $this->secretInput('facebook_client_secret', 'Facebook App Secret'),
                        $this->secretInput('telegram_bot_token', 'Telegram Bot token')->columnSpanFull(),
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
        $state = $this->form->getState();

        foreach (array_filter(Arr::pull($state, 'secrets', []), 'filled') as $key => $value) {
            $settings->setSecret($key, trim($value));
        }

        foreach ($state as $key => $value) {
            $settings->set($key, $value);
        }

        $this->form->fill([...$state, 'secrets' => []]);

        Notification::make()->title('Đã lưu cài đặt.')->success()->send();
    }

    /**
     * Ô nhập khoá bí mật: không đổ giá trị cũ ra trình duyệt, chỉ báo đang dùng nguồn nào.
     */
    private function secretInput(string $key, string $label): TextInput
    {
        return TextInput::make("secrets.{$key}")
            ->label($label)
            ->password()
            ->revealable()
            ->autocomplete('new-password')
            ->maxLength(500)
            ->placeholder(fn (): string => match (true) {
                app(Settings::class)->hasStoredSecret($key) => 'Đã lưu trong hệ thống',
                filled(config(Settings::SECRETS[$key])) => 'Đang dùng giá trị trong .env',
                default => 'Chưa cấu hình',
            });
    }
}
