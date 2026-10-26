<?php

use App\Models\Platform;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Settings;
use Database\Seeders\BlogPostSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->platform = shopeeWithDefaultRule();
});

it('lists public pages, platform voucher pages and published posts in the sitemap', function () {
    $category = PostCategory::query()->create(['name' => 'Mẹo săn sale', 'slug' => 'meo-san-sale']);
    $post = Post::factory()->for($category, 'category')->create();
    $draft = Post::factory()->create(['status' => 'draft']);
    Voucher::factory()->for($this->platform)->create();
    Platform::factory()->code('lazada', 'Lazada')->create(['is_active' => false]);
    Platform::factory()->code('tiktok', 'TikTok Shop')->create();

    $response = $this->get(route('sitemap'))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertHeaderMissing('Set-Cookie')
        ->assertSee('<loc>'.route('vouchers.platform', $this->platform).'</loc>', false)
        ->assertSee('<loc>'.route('posts.show', $post).'</loc>', false)
        ->assertSee('<loc>'.route('posts.category', $category).'</loc>', false)
        ->assertSee('<loc>'.route('pages.guide').'</loc>', false)
        ->assertDontSee(route('posts.show', $draft))
        ->assertDontSee('/ma-giam-gia/lazada')
        ->assertDontSee('/ma-giam-gia/tiktok');

    expect($response->getContent())->toStartWith('<?xml version="1.0" encoding="UTF-8"?>'."\n<urlset")
        ->and(simplexml_load_string($response->getContent()))->not->toBeFalse();
});

it('points crawlers to the sitemap and keeps them out of private pages', function () {
    $this->get('/robots.txt')
        ->assertSuccessful()
        ->assertHeaderMissing('Set-Cookie')
        ->assertSee("User-agent: facebookexternalhit\nUser-agent: Facebot\nUser-agent: Twitterbot\nAllow: /\n", false)
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

it('keeps empty platform voucher pages out of the index', function () {
    $this->get(route('vouchers.platform', $this->platform))
        ->assertSuccessful()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});

it('gives paginated blog pages their own title', function () {
    $this->get(route('posts.index', ['page' => 2]))
        ->assertSee('<title>Blog mẹo săn sale, hoàn tiền và kiếm tiền affiliate - Trang 2 - Ví Hoàn Tiền</title>', false);
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
    Storage::fake('uploads');
    Storage::disk('uploads')->put('posts/hoan-tien-la-gi.jpg', 'jpg');

    $this->seed(BlogPostSeeder::class);

    $post = Post::query()->where('slug', 'hoan-tien-la-gi')->sole();
    expect($post->thumbnail)->toBe('posts/hoan-tien-la-gi.jpg')
        ->and($post->thumbnailUrl())->toBe(Storage::disk('uploads')->url('posts/hoan-tien-la-gi.jpg'))
        ->and(Post::query()->where('slug', 'meo-san-flash-sale-shopee')->value('thumbnail'))->toBeNull();
    $post->update(['title' => 'Đã sửa', 'thumbnail' => null]);
    $edited = Post::query()->where('slug', 'shopee-xu-la-gi')->sole();
    $edited->update(['thumbnail' => 'posts/anh-tu-chon.jpg']);

    $this->seed(BlogPostSeeder::class);

    expect(Post::query()->published()->count())->toBe(17)
        ->and($post->fresh()->title)->toBe('Đã sửa')
        ->and($post->fresh()->thumbnail)->toBe('posts/hoan-tien-la-gi.jpg')
        ->and($edited->fresh()->thumbnail)->toBe('posts/anh-tu-chon.jpg');

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
