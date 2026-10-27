<?php

use App\Models\Platform;
use App\Models\Voucher;

beforeEach(function () {
    $this->withoutVite();
    // shopeeWithDefaultRule(): người mua nhận 70% hoa hồng
    $this->platform = shopeeWithDefaultRule();
    $this->platform->update(['max_commission_rate' => 10, 'lock_days' => 35]);
});

it('renders a landing page for an active platform', function () {
    Voucher::factory()->for($this->platform)->count(2)->create();

    $this->get(route('platforms.show', $this->platform))
        ->assertOk()
        ->assertSee('<title>Hoàn tiền Shopee – nhận lại đến 7% mỗi đơn', false)
        ->assertSee('Cách nhận hoàn tiền Shopee')
        ->assertSee('Tỷ lệ hoàn tiền Shopee')
        ->assertSee('~35 ngày')
        ->assertSee('Mã giảm giá Shopee')
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('index, follow', false)
        ->assertSeeLivewire('link-generator');
});

it('shows the admin written content for the platform', function () {
    $this->platform->update(['landing_content' => '<h2>Mẹo săn sale Shopee 11.11</h2><p>Nên mua lúc 0h.</p>']);

    $this->get(route('platforms.show', $this->platform))->assertSee('Mẹo săn sale Shopee 11.11', false);
});

it('shows coming soon platforms without the link generator and keeps them out of search', function () {
    $apple = Platform::factory()->code('apple', 'Apple')->create(['is_active' => false, 'is_coming_soon' => true]);

    $this->get(route('platforms.show', $apple))
        ->assertOk()
        ->assertSee('Sắp ra mắt')
        ->assertSee('noindex, follow', false)
        ->assertDontSeeLivewire('link-generator');
});

it('hides disabled platforms', function () {
    $hidden = Platform::factory()->code('lazada', 'Lazada')->create(['is_active' => false, 'is_coming_soon' => false]);

    $this->get(route('platforms.show', $hidden))->assertNotFound();
});

it('links to platform pages from the home page and the sitemap', function () {
    $this->get(route('home'))->assertSee(route('platforms.show', $this->platform), false);
    $this->get(route('sitemap'))->assertSee(route('platforms.show', $this->platform), false);
});
