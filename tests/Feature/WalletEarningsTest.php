<?php

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Services\WalletService;

beforeEach(function () {
    $this->withoutVite();
    $this->user = User::factory()->create();
    $this->wallet = app(WalletService::class);
});

it('charts monthly earnings by group for the last 12 months', function () {
    $this->travelTo(now()->startOfMonth()->subMonths(2)->addDays(3));
    $this->wallet->post($this->user, WalletTransactionType::Cashback, WalletBucket::Available, 40000, description: 'Hoàn tiền tháng trước nữa');
    $this->travelBack();

    $this->wallet->post($this->user, WalletTransactionType::Cashback, WalletBucket::Available, 120000, description: 'Hoàn tiền');
    $this->wallet->post($this->user, WalletTransactionType::Referral, WalletBucket::Available, 30000, description: 'Giới thiệu');
    $this->wallet->post($this->user, WalletTransactionType::Checkin, WalletBucket::Available, 500, description: 'Điểm danh');
    $this->wallet->post($this->user, WalletTransactionType::Bonus, WalletBucket::Available, 10000, description: 'Thưởng');
    // Tiền chờ duyệt và tiền rút không tính là thu nhập đã về ví
    $this->wallet->post($this->user, WalletTransactionType::Cashback, WalletBucket::Pending, 99000, description: 'Chờ duyệt');

    $response = $this->actingAs($this->user)->get(route('dashboard.wallet.index'))->assertOk();

    $earnings = $response->viewData('earnings');
    $thisMonth = collect($earnings['months'])->last();
    $twoMonthsAgo = $earnings['months'][9];

    expect($earnings['months'])->toHaveCount(12)
        ->and($thisMonth['values'])->toBe(['cashback' => 120000, 'referral' => 30000, 'bonus' => 10500])
        ->and($thisMonth['total'])->toBe(160500)
        ->and($twoMonthsAgo['values']['cashback'])->toBe(40000)
        ->and($earnings['total'])->toBe(200500);

    $response->assertSee('Thu nhập theo tháng')->assertSee('Xem dạng bảng')->assertSee(vnd(200500));
});

it('shows an empty state when there are no earnings yet', function () {
    $this->actingAs($this->user)->get(route('dashboard.wallet.index'))->assertSee('Chưa có thu nhập trong 12 tháng qua');
});

it('exports the transaction history as an Excel friendly csv', function () {
    $this->wallet->post($this->user, WalletTransactionType::Cashback, WalletBucket::Available, 120000, description: 'Hoàn tiền đơn Shopee 123');
    $this->wallet->post($this->user, WalletTransactionType::Cashback, WalletBucket::Pending, 50000, description: 'Ghi nhận đơn Shopee 456');
    $this->wallet->post(User::factory()->create(), WalletTransactionType::Bonus, WalletBucket::Available, 777000, description: 'Của người khác');

    $response = $this->actingAs($this->user)->get(route('dashboard.wallet.export'))->assertOk();
    $csv = $response->streamedContent();

    expect($response->headers->get('Content-Disposition'))->toContain('bien-dong-so-du-'.now()->format('Ymd').'.csv')
        ->and($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($csv)->toContain('"Thời gian",Loại,"Số dư","Số tiền (VND)","Số dư sau (VND)","Nội dung"')
        ->and($csv)->toContain('"Hoàn tiền đơn Shopee 123"')
        ->and($csv)->toContain('"Ghi nhận đơn Shopee 456"')
        ->and($csv)->not->toContain('Của người khác');

    $filtered = $this->get(route('dashboard.wallet.export', ['vi' => 'pending']))->streamedContent();
    expect($filtered)->toContain('Ghi nhận đơn Shopee 456')->not->toContain('Hoàn tiền đơn Shopee 123');
});
