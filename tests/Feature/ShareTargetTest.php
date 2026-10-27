<?php

use App\Livewire\LinkGenerator;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    $this->platform = shopeeWithDefaultRule();
    $this->user = User::factory()->create();
});

it('declares the share target in the web manifest', function () {
    $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);

    expect($manifest['share_target'])->toMatchArray([
        'action' => parse_url(route('dashboard.share-target'), PHP_URL_PATH),
        'method' => 'GET',
    ]);
});

it('pulls the product link out of text shared from the shopee app', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard.share-target', [
            'title' => 'Áo thun nam',
            'text' => 'Xem sản phẩm Áo thun nam với giá chỉ 99.000đ trên Shopee! https://shopee.vn/ao-thun-i.123.456.',
        ]))
        ->assertRedirect(route('dashboard.links.index', ['link' => 'https://shopee.vn/ao-thun-i.123.456', 'auto' => 1]));
});

it('prefers the url field when the app sends one', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard.share-target', ['url' => 'https://shopee.vn/ao-thun-i.123.456', 'text' => 'Xem sản phẩm này']))
        ->assertRedirect(route('dashboard.links.index', ['link' => 'https://shopee.vn/ao-thun-i.123.456', 'auto' => 1]));
});

it('explains when the shared content has no link', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard.share-target', ['text' => 'Chỉ có chữ thôi']))
        ->assertRedirect(route('dashboard.links.index'))
        ->assertSessionHas('error');
});

it('sends guests to log in and back to the shared link', function () {
    $shareUrl = route('dashboard.share-target', ['text' => 'https://shopee.vn/ao-thun-i.123.456']);

    $this->get($shareUrl)->assertRedirect(route('login'));

    $this->post(route('login'), ['login' => $this->user->email, 'password' => 'password'])
        ->assertRedirect($shareUrl);
});

it('creates the cashback link right away when opened from a share', function () {
    Livewire::withQueryParams(['link' => 'https://shopee.vn/ao-thun-i.123.456', 'auto' => '1'])
        ->actingAs($this->user)
        ->test(LinkGenerator::class)
        ->assertSet('autoGenerate', true)
        ->call('generateShared')
        ->assertSet('autoGenerate', false)
        ->assertHasNoErrors();

    expect($this->user->trackingLinks()->count())->toBe(1);
});

it('does not auto create links without the share flag', function () {
    Livewire::withQueryParams(['link' => 'https://shopee.vn/ao-thun-i.123.456'])
        ->actingAs($this->user)
        ->test(LinkGenerator::class)
        ->assertSet('url', 'https://shopee.vn/ao-thun-i.123.456')
        ->call('generateShared');

    expect($this->user->trackingLinks()->count())->toBe(0);
});
