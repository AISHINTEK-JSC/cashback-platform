<?php

use App\Enums\FraudFlagStatus;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\FraudFlags\Pages\ListFraudFlags;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Platforms\Pages\ManagePlatforms;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Withdrawals\Pages\ListWithdrawals;
use App\Models\FraudFlag;
use App\Models\Order;
use App\Models\Post;
use App\Models\Setting;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Models\Withdrawal;
use App\Services\Affiliate\AffiliateManager;
use App\Services\Affiliate\Drivers\AccessTradeDriver;
use App\Services\Orders\OrderRewardService;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\Support\Settings;
use Livewire\Livewire;

beforeEach(function () {
    $this->platform = shopeeWithDefaultRule();
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

function fundedWithdrawal(int $amount = 100000): Withdrawal
{
    $user = User::factory()->create();
    app(WalletService::class)->post($user, WalletTransactionType::Bonus, WalletBucket::Available, 500000);

    return app(WithdrawalService::class)->request($user, UserBankAccount::factory()->for($user)->create(), $amount);
}

it('renders every admin page', function (string $path) {
    $order = Order::factory()->for($this->platform)->create();
    $withdrawal = fundedWithdrawal();
    $ticket = SupportTicket::create(['user_id' => $withdrawal->user_id, 'type' => 'other', 'subject' => 'Hỏi', 'status' => 'open']);
    $ticket->messages()->create(['user_id' => $withdrawal->user_id, 'message' => 'Xin chào']);

    $path = strtr($path, ['{order}' => $order->id, '{withdrawal}' => $withdrawal->id, '{user}' => $withdrawal->user_id, '{ticket}' => $ticket->id]);

    $this->get('/admin'.$path)->assertSuccessful();
})->with([
    '', '/withdrawals', '/withdrawals/{withdrawal}', '/orders', '/orders/{order}', '/users', '/users/{user}', '/users/{user}/edit',
    '/user-levels', '/platforms', '/commission-rules', '/vouchers', '/post-categories', '/posts', '/posts/create',
    '/support-tickets', '/support-tickets/{ticket}', '/fraud-flags', '/wallet-transactions', '/sync-logs', '/activity-logs', '/manage-settings',
]);

it('hides configuration from staff', function () {
    $this->actingAs(User::factory()->create(['role' => 'staff']));

    $this->get('/admin/withdrawals')->assertSuccessful();
    $this->get('/admin/commission-rules')->assertForbidden();
    $this->get('/admin/manage-settings')->assertForbidden();
});

it('marks a withdrawal as paid from the table', function () {
    $withdrawal = fundedWithdrawal();

    Livewire::test(ListWithdrawals::class)
        ->callTableAction('markPaid', $withdrawal, data: ['bank_txn_ref' => 'FT999'])
        ->assertHasNoTableActionErrors();

    expect($withdrawal->fresh())
        ->status->toBe(WithdrawalStatus::Paid)
        ->bank_txn_ref->toBe('FT999')
        ->reviewed_by->toBe($this->admin->id);
});

it('rejects a withdrawal and refunds the member', function () {
    $withdrawal = fundedWithdrawal();

    Livewire::test(ListWithdrawals::class)
        ->callTableAction('reject', $withdrawal, data: ['reason' => 'Sai số tài khoản']);

    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::Rejected)
        ->and($withdrawal->user->wallet->fresh()->available_balance)->toBe(500000);
});

it('approves an order from the order page', function () {
    $buyer = User::factory()->create();
    $order = Order::factory()->for($this->platform)->for($buyer)->create();
    app(OrderRewardService::class)->createRewards($order);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->callAction('changeStatus', data: ['status' => 'approved', 'note' => 'Duyệt tay']);

    expect($order->fresh()->status)->toBe(OrderStatus::Approved)
        ->and($buyer->wallet->fresh()->available_balance)->toBe(28000);
});

it('assigns an unmatched order to a member and creates rewards', function () {
    $buyer = User::factory()->create();
    $order = Order::factory()->for($this->platform)->create(['user_id' => null]);

    Livewire::test(ViewOrder::class, ['record' => $order->id])
        ->callAction('assignUser', data: ['user_id' => $buyer->id]);

    expect($order->fresh()->user_id)->toBe($buyer->id)
        ->and($buyer->wallet->fresh()->pending_balance)->toBe(28000);
});

it('adjusts a member balance through the ledger', function () {
    $user = User::factory()->create();

    Livewire::test(ViewUser::class, ['record' => $user->id])
        ->callAction('adjustBalance', data: ['bucket' => 'available', 'amount' => 15000, 'description' => 'Thưởng sự kiện']);

    expect($user->wallet->fresh()->available_balance)->toBe(15000)
        ->and($user->walletTransactions()->sole()->created_by)->toBe($this->admin->id);
});

it('saves settings', function () {
    Livewire::test(ManageSettings::class)
        ->set('data.withdraw_min', 20000)
        ->call('save')
        ->assertHasNoErrors();

    expect(setting('withdraw_min'))->toBe(20000);
});

it('stores integration keys encrypted and keeps them when left blank', function () {
    Livewire::test(ManageSettings::class)
        ->set('data.secrets.accesstrade_api_key', ' at-key-123 ')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('data.secrets.accesstrade_api_key', null);

    $stored = Setting::query()->find('accesstrade_api_key')->value;

    expect($stored)->not->toContain('at-key-123')
        ->and(app(Settings::class)->secret('accesstrade_api_key'))->toBe('at-key-123');

    Livewire::test(ManageSettings::class)->call('save')->assertHasNoErrors();

    expect(app(Settings::class)->secret('accesstrade_api_key'))->toBe('at-key-123');
});

it('falls back to the env value when no integration key is stored', function () {
    config(['services.google.client_id' => 'env-google-id']);

    expect(app(Settings::class)->secret('google_client_id'))->toBe('env-google-id');
});

it('lets admins pick the affiliate driver of a platform', function () {
    config(['cashback.drivers.shopee' => 'fake']);

    Livewire::test(ManagePlatforms::class)
        ->callTableAction('edit', $this->platform, data: ['driver' => 'accesstrade'])
        ->assertHasNoTableActionErrors();

    expect(app(AffiliateManager::class)->driver($this->platform->fresh()))->toBeInstanceOf(AccessTradeDriver::class);
});

it('confirms a fraud flag and locks the member', function () {
    $flag = FraudFlag::create(['user_id' => User::factory()->create()->id, 'reason' => 'shared_bank', 'score' => 80, 'status' => 'open']);

    Livewire::test(ListFraudFlags::class)
        ->callTableAction('confirm', $flag, data: ['lock_user' => true]);

    expect($flag->fresh()->status)->toBe(FraudFlagStatus::Confirmed)
        ->and($flag->user->fresh()->status)->toBe(UserStatus::Locked);
});

it('writes a blog post from the admin panel and publishes it right away', function () {
    Livewire::test(CreatePost::class)
        ->fillForm([
            'title' => 'Mẹo săn sale cuối năm',
            'slug' => 'meo-san-sale-cuoi-nam',
            'content' => '<h2>Chuẩn bị</h2><p>Lập danh sách trước.</p>',
            'status' => 'published',
            'published_at' => null,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::query()->where('slug', 'meo-san-sale-cuoi-nam')->sole();

    expect($post->author_id)->toBe($this->admin->id)
        ->and($post->isPublished())->toBeTrue();

    $this->get(route('posts.show', $post))->assertSuccessful()->assertSee('Mẹo săn sale cuối năm')->assertDontSee('Đang xem trước');
    $this->get(route('sitemap'))->assertSee(route('posts.show', $post));
});

it('shows seo length hints and a preview link when editing a post', function () {
    $post = Post::factory()->create(['status' => 'draft', 'title' => 'Bài nháp', 'meta_description' => str_repeat('a', 170)]);

    Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
        ->assertSee('Mô tả trên Google: 170/160 ký tự, dài quá')
        ->assertActionExists('open')
        ->assertActionHasLabel('open', 'Xem trước');
});
