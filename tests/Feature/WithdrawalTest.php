<?php

use App\Enums\PhoneCarrier;
use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalMethod;
use App\Enums\WithdrawalStatus;
use App\Exceptions\BusinessRuleException;
use App\Livewire\WithdrawForm;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

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

it('tops up a phone below the bank transfer minimum without a fee', function () {
    app(Settings::class)->set('withdraw_fee', 5000);

    $withdrawal = $this->service->requestPhone($this->user, WithdrawalMethod::PhoneTopup, PhoneCarrier::Viettel, 10000, '+84 98 123 4567');

    expect($withdrawal)
        ->method->toBe(WithdrawalMethod::PhoneTopup)
        ->phone_carrier->toBe(PhoneCarrier::Viettel)
        ->phone_number->toBe('0981234567')
        ->fee->toBe(0)
        ->net_amount->toBe(10000)
        ->bank_name->toBeNull();

    $wallet = $this->user->wallet->fresh();
    expect($wallet->available_balance)->toBe(190000)
        ->and($wallet->frozen_balance)->toBe(10000);
});

it('only accepts the configured phone denominations', function () {
    $this->service->requestPhone($this->user, WithdrawalMethod::PhoneCard, PhoneCarrier::Mobifone, 15000);
})->throws(BusinessRuleException::class, 'Mệnh giá không hợp lệ');

it('requires a valid mobile number for phone top-ups', function () {
    $this->service->requestPhone($this->user, WithdrawalMethod::PhoneTopup, PhoneCarrier::Viettel, 10000, '0241234567');
})->throws(BusinessRuleException::class, 'Số điện thoại không hợp lệ');

it('refuses phone payouts the admin turned off', function () {
    app(Settings::class)->set('phone_card_enabled', false);

    $this->service->requestPhone($this->user, WithdrawalMethod::PhoneCard, PhoneCarrier::Viettel, 10000);
})->throws(BusinessRuleException::class, 'tạm ngưng');

it('shares the open request limit with bank withdrawals', function () {
    $this->service->request($this->user, $this->bank, 50000);
    $this->service->requestPhone($this->user, WithdrawalMethod::PhoneCard, PhoneCarrier::Viettel, 10000);
})->throws(BusinessRuleException::class, 'chưa xử lý xong');

it('delivers a scratch card with an encrypted code and keeps the code out of notifications', function () {
    $withdrawal = $this->service->requestPhone($this->user, WithdrawalMethod::PhoneCard, PhoneCarrier::Vinaphone, 20000);

    expect(fn () => $this->service->markPaid($withdrawal, $this->admin))->toThrow(BusinessRuleException::class, 'serial và mã thẻ');

    $this->service->deliverCard($withdrawal, $this->admin, ' 10001234567 ', '123456789012345');

    $withdrawal->refresh();
    expect($withdrawal)
        ->status->toBe(WithdrawalStatus::Paid)
        ->card_serial->toBe('10001234567')
        ->card_code->toBe('123456789012345')
        ->and(DB::table('withdrawals')->where('id', $withdrawal->id)->value('card_code'))->not->toContain('123456789012345')
        ->and($this->user->wallet->fresh()->total_withdrawn)->toBe(20000);

    $notification = $this->user->notifications()->latest()->first();
    expect($notification->data['body'])->toContain('Thẻ cào Vinaphone')->not->toContain('123456789012345');
});

it('shows a delivered card code only to its owner', function () {
    $withdrawal = $this->service->requestPhone($this->user, WithdrawalMethod::PhoneCard, PhoneCarrier::Viettel, 50000);
    $this->service->deliverCard($withdrawal, $this->admin, '10009998887', '999988887777666');

    $this->actingAs($this->user)
        ->get(route('dashboard.withdrawals.index'))
        ->assertOk()
        ->assertSee('Thẻ cào Viettel')
        ->assertSee('999988887777666');

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard.withdrawals.index'))
        ->assertOk()
        ->assertDontSee('999988887777666');
});

it('creates a phone top-up request from the withdraw form', function () {
    Livewire::actingAs($this->user)
        ->test(WithdrawForm::class)
        ->assertSet('method', 'bank')
        ->set('method', 'phone_topup')
        ->set('carrier', 'viettel')
        ->set('phoneNumber', '0981234567')
        ->set('amount', 20000)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard.withdrawals.index'));

    expect($this->user->withdrawals()->sole())
        ->method->toBe(WithdrawalMethod::PhoneTopup)
        ->phone_number->toBe('0981234567')
        ->amount->toBe(20000);
});

it('validates the phone number in the withdraw form', function () {
    Livewire::actingAs($this->user)
        ->test(WithdrawForm::class)
        ->set('method', 'phone_topup')
        ->set('carrier', 'viettel')
        ->set('phoneNumber', '12345')
        ->set('amount', 20000)
        ->call('submit')
        ->assertHasErrors('phoneNumber');

    expect($this->user->withdrawals()->exists())->toBeFalse();
});

it('hides phone payouts from the withdraw form when turned off', function () {
    app(Settings::class)->set('phone_topup_enabled', false);
    app(Settings::class)->set('phone_card_enabled', false);

    Livewire::actingAs($this->user)
        ->test(WithdrawForm::class)
        ->assertDontSee('Nạp điện thoại')
        ->set('method', 'phone_card')
        ->set('carrier', 'viettel')
        ->set('amount', 10000)
        ->call('submit')
        ->assertHasErrors('method');
});
