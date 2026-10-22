<?php

use App\Enums\UserStatus;
use App\Models\ActivityLog;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

it('registers a member with a referral code and creates a wallet', function () {
    $referrer = User::factory()->create();

    $this->post(route('register'), [
        'name' => 'Trần Thị B',
        'email' => 'b@example.com',
        'phone' => '0912345678',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
        'referral_code' => $referrer->referral_code,
        'terms' => '1',
    ])->assertRedirect(route('dashboard.index'));

    $user = User::where('email', 'b@example.com')->sole();
    expect($user->referred_by)->toBe($referrer->id)
        ->and($user->wallet)->not->toBeNull()
        ->and($user->referral_code)->toHaveLength(8)
        ->and(ActivityLog::where('user_id', $user->id)->where('action', 'register')->exists())->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

it('picks up the referral code from the referral landing link', function () {
    $referrer = User::factory()->create();

    $this->get(route('referral.landing', $referrer->referral_code))
        ->assertRedirect(route('register'))
        ->assertCookie('ref', $referrer->referral_code);
});

it('logs in with email or phone', function () {
    $user = User::factory()->create(['phone' => '0987654321']);

    $this->post(route('login'), ['login' => '0987654321', 'password' => 'password'])
        ->assertRedirect(route('dashboard.index'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects wrong credentials', function () {
    User::factory()->create(['email' => 'a@example.com']);

    $this->post(route('login'), ['login' => 'a@example.com', 'password' => 'sai-mat-khau'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('blocks locked accounts', function () {
    $user = User::factory()->locked()->create();

    $this->post(route('login'), ['login' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('logs out a member who gets locked mid-session', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $user->update(['status' => UserStatus::Locked]);

    $this->get(route('dashboard.index'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('only lets staff into the admin panel', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSuccessful();
});
