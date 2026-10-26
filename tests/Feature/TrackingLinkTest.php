<?php

use App\Exceptions\BusinessRuleException;
use App\Livewire\LinkGenerator;
use App\Models\Platform;
use App\Models\TrackingLink;
use App\Models\User;
use App\Services\TrackingLinkService;
use Livewire\Livewire;

beforeEach(function () {
    $this->platform = shopeeWithDefaultRule();
    $this->user = User::factory()->create();
});

it('creates a tracking link with a unique sub_id for a supported platform', function () {
    $link = app(TrackingLinkService::class)->create($this->user, 'https://shopee.vn/ao-thun-i.123.456');

    expect($link->platform_id)->toBe($this->platform->id)
        ->and($link->sub_id)->toMatch('/^[a-z0-9]+$/')
        ->and($link->affiliate_url)->toContain($link->sub_id)
        ->and((float) $link->estimated_rate)->toBeGreaterThan(0);
});

it('rejects links from unsupported sites', function () {
    app(TrackingLinkService::class)->create($this->user, 'https://tiki.vn/san-pham');
})->throws(BusinessRuleException::class, 'chưa được hỗ trợ');

it('tells users when a platform is coming soon instead of creating a link', function () {
    Platform::factory()->code('apple', 'Apple')->create(['is_active' => false, 'is_coming_soon' => true]);

    app(TrackingLinkService::class)->create($this->user, 'https://www.apple.com/vn/shop/buy-iphone');
})->throws(BusinessRuleException::class, 'Hoàn tiền Apple sắp ra mắt');

it('rejects values that are not links', function () {
    app(TrackingLinkService::class)->create($this->user, 'not a url');
})->throws(BusinessRuleException::class, 'không hợp lệ');

it('counts clicks and redirects to the affiliate url', function () {
    $link = TrackingLink::factory()->for($this->user)->for($this->platform)->create();

    $this->get(route('links.redirect', $link->sub_id))->assertRedirect($link->affiliate_url);

    expect($link->fresh()->click_count)->toBe(1);
});

it('generates a link from the livewire widget', function () {
    Livewire::actingAs($this->user)
        ->test(LinkGenerator::class)
        ->set('url', 'https://shopee.vn/ao-thun-i.123.456')
        ->call('generate')
        ->assertHasNoErrors()
        ->assertSee('Mua ngay trên Shopee')
        ->assertDispatched('cashback-link-created', platform: 'shopee');

    expect(TrackingLink::count())->toBe(1);
});

it('sends guests to the login page when they try to generate a link', function () {
    Livewire::test(LinkGenerator::class)
        ->set('url', 'https://shopee.vn/ao-thun-i.123.456')
        ->call('generate')
        ->assertRedirect(route('login'));
});
