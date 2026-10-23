<?php

use App\Enums\OrderStatus;
use App\Models\CommissionRule;
use App\Models\Order;
use App\Models\Platform;
use App\Models\TrackingLink;
use App\Models\User;
use App\Services\Affiliate\AffiliateException;
use App\Services\Affiliate\Drivers\AccessTradeDriver;
use App\Services\Orders\OrderSyncService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.accesstrade.key' => 'test-key',
        'services.accesstrade.endpoint' => 'https://api.accesstrade.test/v1',
    ]);

    $this->platform = Platform::factory()->code('tiktok', 'TikTok Shop')->create();
    $this->driver = new AccessTradeDriver;
});

/**
 * Một dòng giao dịch như API /transactions trả về.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function accessTradeRow(array $overrides = []): array
{
    return array_replace([
        'transaction_id' => '586231892225525290',
        'transaction_time' => '2026-09-24T16:48:52',
        'time_zone' => '+00:00',
        'product_id' => '1736838537033778949',
        'product_name' => 'Phấn phủ PRAMY',
        'category_name' => '',
        'product_quantity' => 1,
        'product_price' => 296800.0,
        'transaction_value' => 296800.0,
        'commission' => 29680.0,
        'is_brand_bonus' => false,
        'status' => 0,
        'utm_content' => 'sub123',
        '_extra' => ['sub_params' => ['sub1' => 'sub123']],
    ], $overrides);
}

it('creates a deep link for the platform campaign carrying the sub_id', function () {
    Http::fake(['*/product_link/create' => Http::response(['success' => true, 'data' => ['success_link' => [[
        'aff_link' => 'https://go.isclix.com/deep_link/1/6648523843406889655?sub1=sub123',
        'short_link' => 'https://shorten.asia/abc',
    ]], 'error_link' => []]])]);

    $link = $this->driver->generateLink($this->platform, 'https://www.tiktok.com/view/product/1729384756473829384', 'sub123');

    expect($link->affiliateUrl)->toBe('https://shorten.asia/abc')
        ->and($link->productId)->toBe('1729384756473829384');

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Token test-key')
        && $request['campaign_id'] === '6648523843406889655'
        && $request['sub1'] === 'sub123'
        && $request['urls'] === ['https://www.tiktok.com/view/product/1729384756473829384']);
});

it('uses the campaign configured on the platform over the default', function () {
    $this->platform->update(['api_config' => ['campaign_id' => '999']]);
    Http::fake(['*' => Http::response(['data' => ['success_link' => [['aff_link' => 'https://aff', 'short_link' => null]]]])]);

    expect($this->driver->generateLink($this->platform, 'https://www.tiktok.com/x', 'sub123')->affiliateUrl)->toBe('https://aff');

    Http::assertSent(fn (Request $request) => $request['campaign_id'] === '999');
});

it('reports why AccessTrade refused to create a link', function () {
    Http::fake(['*' => Http::response(['success' => false, 'data' => ['success_link' => [], 'error_link' => [
        ['message' => 'The link is not part of the campaign'],
    ]]])]);

    $this->driver->generateLink($this->platform, 'https://shopee.vn/product/1/2', 'sub123');
})->throws(AffiliateException::class, 'The link is not part of the campaign');

it('refuses to call the api without a key', function () {
    config(['services.accesstrade.key' => null]);
    Http::fake();

    $this->driver->generateLink($this->platform, 'https://www.tiktok.com/x', 'sub123');
})->throws(AffiliateException::class, 'ACCESSTRADE_API_KEY');

it('groups transaction rows into one order per transaction id', function () {
    Http::fake(['*/transactions*' => Http::response(['total' => 3, 'data' => [
        accessTradeRow(['status' => 1]),
        accessTradeRow(['product_id' => 'bonus', 'product_name' => '', 'is_brand_bonus' => true, 'transaction_value' => 29680.0, 'commission' => 19046.0, 'status' => 1]),
        accessTradeRow(['transaction_id' => 'other', 'status' => 2, '_extra' => ['sub_params' => []], 'utm_content' => '']),
    ]])]);

    $orders = collect($this->driver->fetchOrders($this->platform, Carbon::parse('2026-09-20'), Carbon::parse('2026-09-30')))->keyBy('platformOrderId');

    $approved = $orders['586231892225525290'];
    expect($approved->subId)->toBe('sub123')
        ->and($approved->orderAmount)->toBe(296800)
        ->and($approved->commissionAmount)->toBe(48726)
        ->and($approved->status)->toBe(OrderStatus::Confirmed)
        ->and($approved->items)->toHaveCount(2)
        ->and($approved->items[1]->itemName)->toBe('Thưởng thêm từ nhãn hàng')
        ->and($approved->purchasedAt->format('Y-m-d H:i'))->toBe('2026-09-24 23:48');

    $rejected = $orders['other'];
    expect($rejected->status)->toBe(OrderStatus::Cancelled)
        ->and($rejected->commissionAmount)->toBe(0)
        ->and($rejected->subId)->toBeNull();

    Http::assertSent(fn (Request $request) => $request['merchant'] === 'tiktok_cps');
});

it('keeps an order pending while any item is still pending', function () {
    Http::fake(['*' => Http::response(['total' => 2, 'data' => [
        accessTradeRow(['status' => 1]),
        accessTradeRow(['product_id' => 'second', 'status' => 0]),
    ]])]);

    $order = collect($this->driver->fetchOrders($this->platform, now()->subDay(), now()))->sole();

    expect($order->status)->toBe(OrderStatus::Pending);
});

it('splits long sync windows into chunks the api accepts', function () {
    Http::fake(['*' => Http::response(['total' => 0, 'data' => []])]);

    iterator_to_array($this->driver->fetchOrders($this->platform, Carbon::parse('2026-08-01'), Carbon::parse('2026-10-15')));

    Http::assertSentCount(3);
});

it('surfaces api errors so the sync is logged as failed', function () {
    Http::fake(['*' => Http::response(['status' => 'fail', 'message' => 'sales date range must be less than 31 days.'])]);

    iterator_to_array($this->driver->fetchOrders($this->platform, now()->subDay(), now()));
})->throws(AffiliateException::class, 'less than 31 days');

it('syncs AccessTrade orders to the member who created the link', function () {
    config(['cashback.drivers.tiktok' => 'accesstrade']);
    CommissionRule::factory()->for($this->platform)->create();
    $user = User::factory()->create();
    TrackingLink::factory()->for($user)->for($this->platform)->create(['sub_id' => 'sub123']);
    Http::fake(['*' => Http::response(['total' => 1, 'data' => [accessTradeRow()]])]);

    app(OrderSyncService::class)->sync($this->platform, Carbon::parse('2026-09-20'), Carbon::parse('2026-09-30'));

    $order = Order::query()->sole();
    expect($order->user_id)->toBe($user->id)
        ->and($order->platform_order_id)->toBe('586231892225525290')
        ->and($order->commission_amount)->toBe(29680)
        ->and($order->status)->toBe(OrderStatus::Pending);
});
