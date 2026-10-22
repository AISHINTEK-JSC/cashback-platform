<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Post;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Voucher;

beforeEach(function () {
    $this->withoutVite();
    $this->platform = shopeeWithDefaultRule();
});

it('renders the public pages', function (string $route) {
    Voucher::factory()->for($this->platform)->create();
    Post::factory()->create();

    $this->get(route($route))->assertSuccessful();
})->with(['home', 'vouchers.index', 'rates', 'posts.index', 'pages.guide', 'pages.faq', 'pages.terms', 'pages.privacy', 'login', 'register', 'password.request']);

it('renders a published post and hides drafts', function () {
    $post = Post::factory()->create();
    $draft = Post::factory()->create(['status' => 'draft']);

    $this->get(route('posts.show', $post))->assertSuccessful()->assertSee($post->title);
    $this->get(route('posts.show', $draft))->assertNotFound();
});

it('requires login for the dashboard', function () {
    $this->get(route('dashboard.index'))->assertRedirect(route('login'));
});

it('renders every dashboard page for a member', function (string $route) {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route($route))->assertSuccessful();
})->with([
    'dashboard.index', 'dashboard.links.index', 'dashboard.orders.index', 'dashboard.wallet.index',
    'dashboard.withdrawals.index', 'dashboard.bank-accounts.index', 'dashboard.referrals.index',
    'dashboard.checkins.index', 'dashboard.notifications.index', 'dashboard.saved-products.index',
    'dashboard.tickets.index', 'dashboard.tickets.create', 'dashboard.profile.edit',
]);

it('shows only the member own orders', function () {
    $user = User::factory()->create();
    $mine = Order::factory()->for($this->platform)->for($user)->create(['status' => OrderStatus::Pending]);
    $other = Order::factory()->for($this->platform)->for(User::factory())->create();

    $this->actingAs($user)->get(route('dashboard.orders.show', $mine))->assertSuccessful()->assertSee($mine->platform_order_id);
    $this->actingAs($user)->get(route('dashboard.orders.show', $other))->assertNotFound();
});

it('opens a missing order ticket', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('dashboard.tickets.store'), [
        'type' => 'missing_order',
        'platform_id' => $this->platform->id,
        'platform_order_id' => '240930ABC',
        'subject' => 'Thiếu đơn ngày 30/9',
        'message' => 'Tôi đã mua qua link nhưng chưa thấy đơn.',
    ])->assertRedirect();

    $ticket = SupportTicket::sole();
    expect($ticket->messages)->toHaveCount(1);

    $this->actingAs($user)->get(route('dashboard.tickets.show', $ticket))->assertSuccessful()->assertSee('Thiếu đơn ngày 30/9');
});
