<?php

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Http\Controllers\TransparencyController;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->withoutVite();
    Cache::forget(TransparencyController::CACHE_KEY);
});

function paidWithdrawal(string $name, int $amount, int $hoursToPay): void
{
    $user = User::factory()->create(['name' => $name]);
    app(WalletService::class)->post($user, WalletTransactionType::Bonus, WalletBucket::Available, $amount, description: 'Thưởng');
    $withdrawal = app(WithdrawalService::class)->request($user, UserBankAccount::factory()->for($user)->create(), $amount);

    test()->travel($hoursToPay)->hours();
    app(WithdrawalService::class)->markPaid($withdrawal, User::factory()->admin()->create(), 'FT123');
    test()->travelBack();
}

it('shows public payout numbers without personal details', function () {
    paidWithdrawal('Lê Văn Thành', 150000, hoursToPay: 4);
    paidWithdrawal('Nguyễn Thị Hoa', 250000, hoursToPay: 20);

    $this->get(route('pages.transparency'))
        ->assertOk()
        ->assertSee('Minh bạch chi trả')
        ->assertSee(vnd(400000))
        ->assertSee('20 giờ')
        ->assertSee('L***h · Vietcombank')
        ->assertDontSee('Lê Văn Thành')
        ->assertDontSee(UserBankAccount::query()->first()->account_number)
        ->assertSee('Xem dạng bảng');
});

it('explains that there are no payouts yet', function () {
    $this->get(route('pages.transparency'))->assertOk()->assertSee('Chưa có lần chi trả nào');
});

it('can be turned off by the admin', function () {
    app(Settings::class)->set('transparency_page', false);

    $this->get(route('pages.transparency'))->assertNotFound();
    $this->get(route('home'))->assertDontSee(route('pages.transparency'), false);
});

it('masks member names', function () {
    expect(TransparencyController::maskName('Lê Văn Thành'))->toBe('L***h')
        ->and(TransparencyController::maskName('an'))->toBe('A***n')
        ->and(TransparencyController::maskName(null))->toBe('Thành viên');
});
