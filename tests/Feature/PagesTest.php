<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Platform;
use App\Models\Post;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Voucher;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->platform = shopeeWithDefaultRule();
});

it('renders the public pages', function (string $route) {
    Voucher::factory()->for($this->platform)->create();
    Post::factory()->create();

    $this->get(route($route))->assertSuccessful();
})->with(['home', 'vouchers.index', 'rates', 'posts.index', 'pages.guide', 'pages.faq', 'pages.terms', 'pages.privacy', 'login', 'register', 'password.request']);

it('lists coming soon platforms on the home page', function () {
    Platform::factory()->code('apple', 'Apple')->create(['is_active' => false, 'is_coming_soon' => true]);
    Platform::factory()->code('tiki', 'Tiki')->create(['is_active' => false]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Apple', 'Sắp ra mắt'])
        ->assertDontSee('Tiki');
});

it('shows the max cashback rate of each active platform on the home page', function () {
    $this->platform->update(['max_commission_rate' => 10]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Nơi mua sắm có hoàn tiền')
        ->assertSee(asset('images/platforms/shopee.png'))
        ->assertSeeInOrder(['Hoàn đến', '7%']);
});

it('renders a published post and hides drafts', function () {
    $post = Post::factory()->create();
    $draft = Post::factory()->create(['status' => 'draft']);

    $this->get(route('posts.show', $post))->assertSuccessful()->assertSee($post->title);
    $this->get(route('posts.show', $draft))->assertNotFound();
});

it('lets staff preview drafts and scheduled posts without counting views', function () {
    $draft = Post::factory()->create(['status' => 'draft']);
    $scheduled = Post::factory()->create(['published_at' => now()->addDay()]);

    $this->get(route('posts.show', $scheduled))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('posts.show', $draft))->assertNotFound();

    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('posts.show', $draft))
        ->assertSuccessful()
        ->assertSee('Đang xem trước: bản nháp')
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
    $this->get(route('posts.show', $scheduled))->assertSee('bài hẹn giờ đăng lúc');

    expect($draft->fresh()->view_count)->toBe(0);
});

it('shares pages with the default open graph image and favicons', function () {
    $this->get(route('rates'))
        ->assertSee('<meta property="og:title" content="Tỷ lệ hoàn tiền">', false)
        ->assertSee('<meta property="og:image" content="'.asset('images/og-default.png').'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('<link rel="icon" href="/favicon.svg" type="image/svg+xml">', false)
        ->assertSee('<link rel="canonical" href="'.route('rates').'">', false);

    expect(public_path('images/og-default.png'))->toBeFile()
        ->and(public_path('favicon.ico'))->toBeFile()
        ->and(filesize(public_path('favicon.ico')))->toBeGreaterThan(0);
});

it('shares a post as an article with its thumbnail', function () {
    $post = Post::factory()->create(['thumbnail' => 'posts/cover.jpg']);

    $this->get(route('posts.show', $post))
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('<meta property="og:image" content="'.$post->thumbnailUrl().'">', false)
        ->assertSee('<meta property="article:published_time"', false)
        ->assertDontSee('images/og-default.png');
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

it('stores member uploads on the uploads disk', function () {
    Storage::fake('uploads');
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('dashboard.profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post(route('dashboard.tickets.store'), [
        'type' => 'missing_order',
        'platform_id' => $this->platform->id,
        'platform_order_id' => '240930ABC',
        'subject' => 'Thiếu đơn',
        'message' => 'Kèm ảnh chụp đơn.',
        'attachments' => [UploadedFile::fake()->image('don-hang.jpg')],
    ])->assertSessionHasNoErrors();

    $avatarPath = str($user->fresh()->avatar)->after('/storage/')->toString();
    Storage::disk('uploads')->assertExists($avatarPath);
    Storage::disk('uploads')->assertExists(SupportTicket::sole()->messages()->sole()->attachments[0]);
});

it('shows the branded 404 page for unknown urls', function () {
    $this->get('/trang-khong-ton-tai')
        ->assertNotFound()
        ->assertSee('Không tìm thấy trang')
        ->assertSee(route('vouchers.index'))
        ->assertSee('noindex, follow', false);

    $this->actingAs(User::factory()->create())
        ->get('/blog/bai-viet-khong-ton-tai')
        ->assertNotFound()
        ->assertSee('Ví của tôi');
});

it('shows the maintenance page with the expected downtime', function () {
    Route::get('/test-maintenance', fn () => abort(503, headers: ['Retry-After' => 600]));

    $this->get('/test-maintenance')
        ->assertServiceUnavailable()
        ->assertSee('Hệ thống đang')
        ->assertSee('Dự kiến hoạt động lại sau khoảng 10 phút');
});

it('shows support contacts as icon links in the footer', function () {
    $settings = app(Settings::class);
    $settings->set('support_hotline', '0900000000');
    $settings->set('support_zalo', 'https://zalo.me/0900000000');
    $settings->set('support_facebook', 'https://www.facebook.com/vihoantien');

    $this->get(route('pages.faq'))
        ->assertOk()
        ->assertSee('href="tel:0900000000"', false)
        ->assertSee('aria-label="Chat Zalo hỗ trợ"', false)
        ->assertSee('aria-label="Fanpage Facebook"', false);
});

it('shows share links on published posts but not on previews', function () {
    $post = Post::factory()->create();
    $encodedUrl = rawurlencode(route('posts.show', $post));

    $this->get(route('posts.show', $post))
        ->assertSuccessful()
        ->assertSee("https://www.facebook.com/sharer/sharer.php?u={$encodedUrl}", false)
        ->assertSee("https://x.com/intent/post?url={$encodedUrl}", false)
        ->assertSee("https://t.me/share/url?url={$encodedUrl}", false)
        ->assertSee('Sao chép link');

    $draft = Post::factory()->create(['status' => 'draft']);
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('posts.show', $draft))
        ->assertSuccessful()
        ->assertDontSee('Chia sẻ lên Facebook');
});

it('renders the terms and privacy pages with live settings', function () {
    app(Settings::class)->set('withdraw_min', 50000);
    app(Settings::class)->set('withdraw_new_bank_hold_hours', 24);

    $this->get(route('pages.terms'))
        ->assertOk()
        ->assertSee('Điều khoản sử dụng')
        ->assertSee(vnd(50000))
        ->assertSee('sau <strong>24 giờ</strong>', false)
        ->assertSee('do một cá nhân sở hữu và vận hành, địa chỉ Tây Mỗ, Hà Nội')
        ->assertDontSee('Bản mẫu');

    $this->get(route('pages.privacy'))
        ->assertOk()
        ->assertSee('Nghị định 13/2023/NĐ-CP')
        ->assertSee('Khi đăng nhập bằng Google')
        ->assertSee('Bên kiểm soát và xử lý dữ liệu')
        ->assertSee('mailto:levanthanh.dev@gmail.com', false)
        ->assertSee('Tây Mỗ, Hà Nội')
        ->assertDontSee('Bản mẫu');
});
