<?php

use App\Enums\BonusType;
use App\Enums\FraudFlagStatus;
use App\Enums\FraudReason;
use App\Enums\OrderStatus;
use App\Enums\OrderStatusSource;
use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Models\Order;
use App\Models\TrackingLink;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Notifications\BonusGranted;
use App\Services\Orders\OrderRewardService;
use App\Services\Orders\OrderStatusService;
use App\Support\Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutVite();
    $this->platform = shopeeWithDefaultRule();
    $this->user = User::factory()->create();
    app(Settings::class)->set('first_order_bonus', 10000);
    app(Settings::class)->set('first_order_bonus_min_order', 50000);
    app(Settings::class)->set('onboarding_bonus', 5000);
});

function approvedOrderFor(User $user, int $orderAmount = 500000): Order
{
    $order = Order::factory()->for(test()->platform)->for($user)->create(['order_amount' => $orderAmount]);
    app(OrderRewardService::class)->createRewards($order);
    app(OrderStatusService::class)->transition($order, OrderStatus::Approved, OrderStatusSource::Admin);

    return $order->fresh();
}

function bonusTransactions(User $user): Collection
{
    return $user->walletTransactions()->whereIn('type', [WalletTransactionType::Bonus, WalletTransactionType::Reversal])->where('reference_type', 'user_bonuses')->get();
}

it('rewards the first approved order once', function () {
    Notification::fake();

    approvedOrderFor($this->user);
    approvedOrderFor($this->user);

    $bonus = $this->user->bonuses()->sole();
    expect($bonus->type)->toBe(BonusType::FirstOrder)
        ->and($bonus->amount)->toBe(10000)
        ->and(bonusTransactions($this->user))->toHaveCount(1)
        ->and(bonusTransactions($this->user)->first()->bucket)->toBe(WalletBucket::Available);
    Notification::assertSentTo($this->user, BonusGranted::class);
});

it('skips the first order bonus for small orders, when turned off or for flagged members', function () {
    approvedOrderFor($this->user, orderAmount: 30000);
    expect($this->user->bonuses()->count())->toBe(0);

    app(Settings::class)->set('first_order_bonus', 0);
    approvedOrderFor($this->user);
    expect($this->user->bonuses()->count())->toBe(0);

    app(Settings::class)->set('first_order_bonus', 10000);
    $flagged = User::factory()->create();
    $flagged->fraudFlags()->create(['reason' => FraudReason::SharedBank, 'score' => 80, 'detail' => [], 'status' => FraudFlagStatus::Confirmed]);
    approvedOrderFor($flagged);
    expect($flagged->bonuses()->count())->toBe(0);
});

it('takes the first order bonus back when that order is cancelled', function () {
    $order = approvedOrderFor($this->user);
    $availableWithBonus = $this->user->wallet->fresh()->available_balance;

    app(OrderStatusService::class)->transition($order, OrderStatus::Cancelled, OrderStatusSource::Admin, 'Hoàn hàng');

    expect($this->user->bonuses()->sole()->revoked_at)->not->toBeNull()
        ->and(bonusTransactions($this->user)->where('type', WalletTransactionType::Reversal)->sole()->amount)->toBe(-10000)
        ->and($this->user->wallet->fresh()->available_balance)->toBeLessThan($availableWithBonus - 10000 + 1);
});

it('walks new members through the onboarding checklist', function () {
    $this->actingAs($this->user)->get(route('dashboard.index'))
        ->assertSee('Bắt đầu nhận hoàn tiền')
        ->assertSee('Thêm tài khoản nhận tiền')
        ->assertSee('Mời 1 người bạn')
        ->assertSee('1/4')
        ->assertDontSee('Nhận thưởng '.vnd(5000));

    $this->post(route('dashboard.onboarding.claim'))->assertSessionHas('error');
    expect($this->user->bonuses()->count())->toBe(0);
});

it('pays the onboarding bonus once all steps are done', function () {
    UserBankAccount::factory()->for($this->user)->create();
    TrackingLink::factory()->for($this->user)->for($this->platform)->create();
    User::factory()->referredBy($this->user)->create();

    $this->actingAs($this->user)->get(route('dashboard.index'))->assertSee('4/4')->assertSee('Nhận thưởng '.vnd(5000));

    $this->post(route('dashboard.onboarding.claim'))->assertSessionHas('success');
    $this->post(route('dashboard.onboarding.claim'))->assertSessionHas('error');

    expect($this->user->bonuses()->where('type', BonusType::Onboarding)->sole()->amount)->toBe(5000)
        ->and($this->user->wallet->fresh()->available_balance)->toBe(5000);

    $this->get(route('dashboard.index'))->assertDontSee('Bắt đầu nhận hoàn tiền');
});

it('only counts invited friends who verified their email', function () {
    UserBankAccount::factory()->for($this->user)->create();
    TrackingLink::factory()->for($this->user)->for($this->platform)->create();
    User::factory()->referredBy($this->user)->unverified()->create();

    $this->actingAs($this->user)->post(route('dashboard.onboarding.claim'))->assertSessionHas('error');
    expect($this->user->bonuses()->count())->toBe(0);
});

it('adds the telegram step when the bot is set up', function () {
    config(['services.telegram.bot_token' => 'test-token']);

    $this->actingAs($this->user)->get(route('dashboard.index'))->assertSee('Liên kết Telegram')->assertSee('1/5');
});
