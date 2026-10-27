<?php

use App\Livewire\LinkGenerator;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;

beforeEach(fn () => $this->withoutVite());

it('asks members who register with email to verify it', function () {
    Notification::fake();

    $this->post(route('register'), [
        'name' => 'Trần Thị B',
        'email' => 'b@example.com',
        'password' => 'matkhau123',
        'password_confirmation' => 'matkhau123',
        'terms' => '1',
    ])->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'b@example.com')->sole();
    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmail::class);

    $this->get(route('verification.notice'))->assertOk()->assertSee('b@example.com');
    $this->get(route('dashboard.index'))->assertRedirect(route('verification.notice'));
});

it('does not ask google sign ups to verify their email', function () {
    Notification::fake();
    Socialite::fake('google', (new SocialiteUser)->map(['id' => 'google-1', 'name' => 'Nguyễn Văn A', 'email' => 'a@gmail.com']));

    $this->get(route('social.callback', 'google'))->assertRedirect(route('dashboard.index'));

    $user = User::where('email', 'a@gmail.com')->sole();
    expect($user->hasVerifiedEmail())->toBeTrue();
    Notification::assertNothingSent();
    $this->get(route('dashboard.index'))->assertOk();
});

it('verifies the email from the signed link', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

    $this->actingAs($user)->get($url)->assertRedirect(route('dashboard.index'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects tampered verification links', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1('khac@example.com')]);

    $this->actingAs($user)->get($url)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))->assertSessionHas('success');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('stops unverified members from generating cashback links', function () {
    $user = User::factory()->unverified()->create();

    Livewire::actingAs($user)
        ->test(LinkGenerator::class)
        ->set('url', 'https://shopee.vn/product/123/456')
        ->call('generate')
        ->assertRedirect(route('verification.notice'));

    expect($user->trackingLinks()->count())->toBe(0);
});
