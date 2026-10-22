<?php

use App\Enums\FraudReason;
use App\Models\FraudFlag;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Services\Fraud\FraudDetector;

it('flags accounts sharing the same bank account number', function () {
    $first = UserBankAccount::factory()->create(['account_number' => '0011223344']);
    $second = UserBankAccount::factory()->create(['account_number' => '0011223344']);

    app(FraudDetector::class)->checkSharedBankAccount($second);

    expect(FraudFlag::sole())
        ->user_id->toBe($second->user_id)
        ->reason->toBe(FraudReason::SharedBank)
        ->detail->toMatchArray(['related_user_ids' => [$first->user_id]]);
});

it('flags self referral when the referrer uses the same bank account', function () {
    $referrer = User::factory()->create();
    $referee = User::factory()->referredBy($referrer)->create();
    UserBankAccount::factory()->for($referrer)->create(['account_number' => '999888777']);
    $account = UserBankAccount::factory()->for($referee)->create(['account_number' => '999888777']);

    app(FraudDetector::class)->checkSharedBankAccount($account);

    expect(FraudFlag::where('reason', FraudReason::SelfReferral)->where('user_id', $referee->id)->exists())->toBeTrue();
});

it('flags a bank account added through the dashboard', function () {
    UserBankAccount::factory()->create(['bank_code' => 'VCB', 'account_number' => '123456789']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('dashboard.bank-accounts.store'), [
            'bank_code' => 'VCB',
            'account_number' => '123456789',
            'account_name' => 'Nguyễn Văn Á',
        ])
        ->assertSessionHas('success');

    expect($user->bankAccounts()->sole()->account_name)->toBe('NGUYEN VAN A')
        ->and(FraudFlag::where('user_id', $user->id)->exists())->toBeTrue();
});
