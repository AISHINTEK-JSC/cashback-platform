<?php

use App\Models\Platform;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Settings;
use Database\Seeders\BlogPostSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->platform = shopeeWithDefaultRule();
});

it('lists public pages, platform voucher pages and published posts in the sitemap', function () {
    $category = PostCategory::query()->create(['name' => 'Mẹo săn sale', 'slug' => 'meo-san-sale']);
    $post = Post::factory()->for($category, 'category')->create();
    $draft = Post::factory()->create(['status' => 'draft']);
    Platform::factory()->code('lazada', 'Lazada')->create(['is_active' => false]);

    $this->get(route('sitemap'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<loc>'.route('vouchers.platform', $this->platform).'</loc>', false)
        ->assertSee('<loc>'.route('posts.show', $post).'</loc>', false)
        ->assertSee('<loc>'.route('posts.category', $category).'</loc>', false)
        ->assertSee('<loc>'.route('pages.guide').'</loc>', false)
        ->assertDontSee(route('posts.show', $draft))
        ->assertDontSee('/ma-giam-gia/lazada');
});

it('points crawlers to the sitemap and keeps them out of private pages', function () {
    $this->get('/robots.txt')
        ->assertSuccessful()
        ->assertSee('Disallow: /tai-khoan')
        ->assertSee('Disallow: /go/')
        ->assertSee('Sitemap: '.route('sitemap'));

    expect(public_path('robots.txt'))->not->toBeFile();
});

it('renders a voucher page per platform', function () {
    $this->platform->update(['max_commission_rate' => 10]);
    $voucher = Voucher::factory()->for($this->platform)->create();
    $other = Voucher::factory()->for(Platform::factory()->code('tiktok', 'TikTok Shop'))->create();
    $post = Post::factory()->create(['title' => 'Cách săn mã giảm giá Shopee']);

    $this->get(route('vouchers.platform', $this->platform))
        ->assertSuccessful()
        ->assertSee('<title>Mã giảm giá Shopee tháng '.now()->format('n/Y'), false)
        ->assertSee($voucher->code)
        ->assertDontSee($other->code)
        ->assertSee('Đơn Shopee được hoàn đến', false)
        ->assertSee($post->title)
        ->assertSee('"@type":"BreadcrumbList"', false);
});

it('hides the voucher page of an inactive platform', function () {
    $lazada = Platform::factory()->code('lazada', 'Lazada')->create(['is_active' => false]);

    $this->get(route('vouchers.platform', $lazada))->assertNotFound();
});

it('redirects the old platform filter to the platform voucher page', function () {
    $this->get(route('vouchers.index', ['san' => 'shopee']))
        ->assertMovedPermanently()
        ->assertRedirect(route('vouchers.platform', $this->platform));

    $this->get(route('vouchers.index', ['san' => 'khong-co']))
        ->assertMovedPermanently()
        ->assertRedirect(route('vouchers.index'));
});

it('adds the search console verification tag', function () {
    app(Settings::class)->set('google_site_verification', 'abc123-XYZ');

    $this->get(route('home'))
        ->assertSee('<meta name="google-site-verification" content="abc123-XYZ">', false)
        ->assertSee('"@type":"WebSite"', false);
});

it('loads google analytics only in production', function () {
    app(Settings::class)->set('google_analytics_id', 'G-TEST123');

    $this->get(route('home'))->assertDontSee('googletagmanager.com');

    app()['env'] = 'production';

    $this->get(route('home'))
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST123', false)
        ->assertSee("gtag('config', 'G-TEST123')", false);
});

it('describes a post as a blog posting', function () {
    $post = Post::factory()->create();

    $this->get(route('posts.show', $post))
        ->assertSee('"@type":"BlogPosting"', false)
        ->assertSee('"headline":'.json_encode($post->title, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG), false);
});

it('keeps the page number in the canonical url of paginated pages', function () {
    $this->get(route('posts.index', ['page' => 2]))
        ->assertSee('<link rel="canonical" href="'.route('posts.index').'?page=2">', false);
});

it('seeds the starter blog posts once without overwriting edits', function () {
    User::factory()->admin()->create();

    $this->seed(BlogPostSeeder::class);

    $post = Post::query()->where('slug', 'hoan-tien-la-gi')->sole();
    $post->update(['title' => 'Đã sửa']);

    $this->seed(BlogPostSeeder::class);

    expect(Post::query()->published()->count())->toBe(5)
        ->and($post->fresh()->title)->toBe('Đã sửa');

    $this->get(route('posts.show', 'cach-san-ma-giam-gia-shopee'))
        ->assertSuccessful()
        ->assertSee('Các loại mã giảm giá trên Shopee');
});

it('keeps login and member pages out of search results', function () {
    $this->get(route('login'))->assertSee('<meta name="robots" content="noindex, follow">', false);
    $this->actingAs(User::factory()->create())->get(route('dashboard.index'))->assertSee('<meta name="robots" content="noindex, follow">', false);
    $this->get(route('home'))->assertSee('<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">', false);
});

it('brands the site as Ví Hoàn Tiền', function () {
    $this->get(route('home'))
        ->assertSee('<title>Ví Hoàn Tiền - Hoàn tiền khi mua Shopee, TikTok Shop, Lazada</title>', false)
        ->assertSee('<meta property="og:site_name" content="Ví Hoàn Tiền">', false);
});
