<?php

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\Support\Settings;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->bank = UserBankAccount::factory()->for($this->user)->create();
    $this->admin = User::factory()->admin()->create();
    $this->service = app(WithdrawalService::class);

    app(WalletService::class)->post($this->user, WalletTransactionType::Bonus, WalletBucket::Available, 200000, description: 'Thưởng');
});

it('freezes the requested amount', function () {
    $withdrawal = $this->service->request($this->user, $this->bank, 100000);

    expect($withdrawal->code)->toBe(sprintf('RT%06d', $withdrawal->id))
        ->and($withdrawal->status)->toBe(WithdrawalStatus::Pending);

    $wallet = $this->user->wallet->fresh();
    expect($wallet->available_balance)->toBe(100000)
        ->and($wallet->frozen_balance)->toBe(100000);
});

it('applies the configured fee', function () {
    app(Settings::class)->set('withdraw_fee', 5000);

    $withdrawal = $this->service->request($this->user, $this->bank, 100000);

    expect($withdrawal->fee)->toBe(5000)
        ->and($withdrawal->net_amount)->toBe(95000);
});

it('rejects amounts above the available balance', function () {
    $this->service->request($this->user, $this->bank, 300000);
})->throws(BusinessRuleException::class, 'Số dư không đủ');

it('rejects amounts below the minimum', function () {
    $this->service->request($this->user, $this->bank, 10000);
})->throws(BusinessRuleException::class, 'tối thiểu');

it('allows only one open request at a time', function () {
    $this->service->request($this->user, $this->bank, 50000);
    $this->service->request($this->user, $this->bank, 50000);
})->throws(BusinessRuleException::class, 'chưa xử lý xong');

it('does not allow using another user bank account', function () {
    $this->service->request($this->user, UserBankAccount::factory()->create(), 50000);
})->throws(BusinessRuleException::class);

it('clears the frozen amount and counts it as withdrawn when paid', function () {
    $withdrawal = $this->service->request($this->user, $this->bank, 100000);

    $this->service->markPaid($withdrawal, $this->admin, 'FT123');

    $wallet = $this->user->wallet->fresh();
    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::Paid)
        ->and($withdrawal->fresh()->bank_txn_ref)->toBe('FT123')
        ->and($wallet->frozen_balance)->toBe(0)
        ->and($wallet->available_balance)->toBe(100000)
        ->and($wallet->total_withdrawn)->toBe(100000);
});

it('refunds the frozen amount when rejected', function () {
    $withdrawal = $this->service->request($this->user, $this->bank, 100000);

    $this->service->reject($withdrawal, $this->admin, 'Sai tên chủ tài khoản');

    $wallet = $this->user->wallet->fresh();
    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::Rejected)
        ->and($wallet->frozen_balance)->toBe(0)
        ->and($wallet->available_balance)->toBe(200000);
});

it('cannot pay a rejected withdrawal twice', function () {
    $withdrawal = $this->service->request($this->user, $this->bank, 100000);
    $this->service->reject($withdrawal, $this->admin, 'Sai STK');

    $this->service->markPaid($withdrawal, $this->admin);
})->throws(BusinessRuleException::class);

it('lets the member cancel a pending request from the dashboard', function () {
    $withdrawal = $this->service->request($this->user, $this->bank, 100000);

    $this->actingAs($this->user)
        ->post(route('dashboard.withdrawals.cancel', $withdrawal))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::Cancelled)
        ->and($this->user->wallet->fresh()->available_balance)->toBe(200000);
});

it('does not let a member cancel someone else request', function () {
    $withdrawal = $this->service->request($this->user, $this->bank, 100000);

    $this->actingAs(User::factory()->create())
        ->post(route('dashboard.withdrawals.cancel', $withdrawal))
        ->assertSessionHas('error');

    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::Pending);
});

it('holds withdrawals to a newly added bank account', function () {
    $newBank = UserBankAccount::factory()->for($this->user)->justAdded()->create(['is_default' => false]);

    expect(fn () => $this->service->request($this->user, $newBank, 100000))
        ->toThrow(BusinessRuleException::class, 'mới được thêm');
    expect($this->user->wallet->fresh()->available_balance)->toBe(200000);

    $this->travel(25)->hours();

    expect($this->service->request($this->user, $newBank->fresh(), 100000)->status)->toBe(WithdrawalStatus::Pending);
});

it('lets admins turn the new bank account hold off', function () {
    app(Settings::class)->set('withdraw_new_bank_hold_hours', 0);
    $newBank = UserBankAccount::factory()->for($this->user)->justAdded()->create(['is_default' => false]);

    expect($this->service->request($this->user, $newBank, 100000)->status)->toBe(WithdrawalStatus::Pending);
});

it('shows when a new bank account can receive withdrawals', function () {
    $newBank = UserBankAccount::factory()->for($this->user)->justAdded()->create(['is_default' => false]);

    $this->actingAs($this->user)
        ->get(route('dashboard.bank-accounts.index'))
        ->assertOk()
        ->assertSee('Nhận được tiền rút từ '.$newBank->created_at->addDay()->format('H:i d/m/Y'));
});
