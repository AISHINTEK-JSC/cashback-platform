<?php

use App\Enums\OrderStatus;
use App\Enums\OrderStatusSource;
use App\Enums\RewardStatus;
use App\Enums\RewardType;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\TrackingLink;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\WalletTransaction;
use App\Services\Affiliate\Data\PlatformOrderData;
use App\Services\Affiliate\Data\PlatformOrderItemData;
use App\Services\Orders\OrderStatusService;
use App\Services\Orders\OrderSyncService;
use App\Services\WalletService;

function orderData(string $subId, OrderStatus $status = OrderStatus::Pending, int $commission = 40000, string $id = 'SP001'): PlatformOrderData
{
    return new PlatformOrderData(
        platformOrderId: $id,
        subId: $subId,
        orderAmount: 500000,
        commissionAmount: $commission,
        status: $status,
        platformStatus: strtoupper($status->value),
        purchasedAt: now()->subDay(),
        items: [new PlatformOrderItemData('item1', 'Áo thun', null, 1, 500000, 8, $commission)],
    );
}

beforeEach(function () {
    $this->platform = shopeeWithDefaultRule();
    $this->f2 = User::factory()->create();
    $this->f1 = User::factory()->referredBy($this->f2)->create();
    $this->buyer = User::factory()->referredBy($this->f1)->create();
    $this->link = TrackingLink::factory()->for($this->buyer)->for($this->platform)->create();
    $this->sync = app(OrderSyncService::class);
});

it('matches a synced order to the buyer by sub_id and splits pending rewards to buyer, F1 and F2', function () {
    expect($this->sync->upsert($this->platform, orderData($this->link->sub_id)))->toBe('inserted');

    $order = Order::sole();
    expect($order->user_id)->toBe($this->buyer->id)
        ->and($order->tracking_link_id)->toBe($this->link->id)
        ->and($order->cashback_amount)->toBe(28000);

    expect($order->rewards->pluck('amount', 'type.value')->sortKeys()->all())->toBe(['f1' => 4000, 'f2' => 2000, 'self' => 28000]);

    expect($this->buyer->wallet->fresh()->pending_balance)->toBe(28000)
        ->and($this->f1->wallet->fresh()->pending_balance)->toBe(4000)
        ->and($this->f2->wallet->fresh()->pending_balance)->toBe(2000);
});

it('is idempotent when the same order is synced again', function () {
    $this->sync->upsert($this->platform, orderData($this->link->sub_id));

    expect($this->sync->upsert($this->platform, orderData($this->link->sub_id)))->toBe('unchanged');

    expect(Order::count())->toBe(1)
        ->and(WalletTransaction::count())->toBe(3)
        ->and($this->buyer->wallet->fresh()->pending_balance)->toBe(28000);
});

it('keeps unmatched orders without rewards', function () {
    $this->sync->upsert($this->platform, orderData('unknown-sub'));

    expect(Order::sole()->user_id)->toBeNull()
        ->and(WalletTransaction::count())->toBe(0);
});

it('moves rewards from pending to available when the order is approved', function () {
    $this->sync->upsert($this->platform, orderData($this->link->sub_id));
    $order = Order::sole();

    app(OrderStatusService::class)->transition($order, OrderStatus::Approved, OrderStatusSource::Admin);

    $wallet = $this->buyer->wallet->fresh();
    expect($wallet->pending_balance)->toBe(0)
        ->and($wallet->available_balance)->toBe(28000)
        ->and($wallet->total_earned)->toBe(28000)
        ->and($order->rewards()->pluck('status')->unique()->all())->toBe([RewardStatus::Available]);

    expect(app(WalletService::class)->recomputeFromLedger($this->buyer->id))
        ->toBe(['pending_balance' => 0, 'available_balance' => 28000, 'frozen_balance' => 0]);
});

it('reverses pending rewards when the platform cancels the order', function () {
    $this->sync->upsert($this->platform, orderData($this->link->sub_id));
    $this->sync->upsert($this->platform, orderData($this->link->sub_id, OrderStatus::Cancelled));

    expect(Order::sole()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->buyer->wallet->fresh()->pending_balance)->toBe(0)
        ->and($this->f1->wallet->fresh()->pending_balance)->toBe(0);
});

it('claws back available money when an approved order is cancelled', function () {
    $this->sync->upsert($this->platform, orderData($this->link->sub_id));
    $statuses = app(OrderStatusService::class);
    $order = Order::sole();
    $statuses->transition($order, OrderStatus::Approved, OrderStatusSource::Admin);

    $statuses->transition($order, OrderStatus::Cancelled, OrderStatusSource::Admin, 'Hoàn hàng');

    $wallet = $this->buyer->wallet->fresh();
    expect($wallet->available_balance)->toBe(0)
        ->and($wallet->total_earned)->toBe(0);
});

it('credits the final commission when the platform lowers it before approval', function () {
    $this->sync->upsert($this->platform, orderData($this->link->sub_id));
    $this->sync->upsert($this->platform, orderData($this->link->sub_id, OrderStatus::Confirmed, commission: 20000));

    $order = Order::sole();
    expect($order->cashback_amount)->toBe(14000);

    app(OrderStatusService::class)->transition($order, OrderStatus::Approved, OrderStatusSource::System);

    $wallet = $this->buyer->wallet->fresh();
    expect($wallet->pending_balance)->toBe(0)
        ->and($wallet->available_balance)->toBe(14000);
});

it('adds the member level bonus on top of the buyer share', function () {
    $gold = UserLevel::factory()->create(['min_approved_orders' => 0, 'bonus_rate' => 10]);
    $this->buyer->update(['level_id' => $gold->id]);

    $this->sync->upsert($this->platform, orderData($this->link->sub_id));

    $self = Order::sole()->rewards()->where('type', RewardType::Self)->sole();
    expect((float) $self->rate)->toBe(77.0)
        ->and($self->amount)->toBe(30800);
});

it('does not pay locked referrers', function () {
    $this->f1->update(['status' => UserStatus::Locked]);

    $this->sync->upsert($this->platform, orderData($this->link->sub_id));

    expect(Order::sole()->rewards->pluck('type.value')->sort()->values()->all())->toBe(['f2', 'self']);
});

it('never lets the sync move an order backwards', function () {
    $this->sync->upsert($this->platform, orderData($this->link->sub_id));
    app(OrderStatusService::class)->transition(Order::sole(), OrderStatus::Approved, OrderStatusSource::System);

    $this->sync->upsert($this->platform, orderData($this->link->sub_id, OrderStatus::Confirmed));

    expect(Order::sole()->status)->toBe(OrderStatus::Approved);
});

it('auto approves confirmed orders once the platform lock period has passed', function () {
    $this->sync->upsert($this->platform, orderData($this->link->sub_id, OrderStatus::Confirmed));
    $this->sync->upsert($this->platform, orderData($this->link->sub_id, OrderStatus::Confirmed, id: 'SP002'));
    Order::where('platform_order_id', 'SP001')->update(['purchased_at' => now()->subDays(31)]);

    $this->artisan('orders:approve-due')->assertSuccessful();

    expect(Order::where('platform_order_id', 'SP001')->value('status'))->toBe(OrderStatus::Approved)
        ->and(Order::where('platform_order_id', 'SP002')->value('status'))->toBe(OrderStatus::Confirmed);
});
