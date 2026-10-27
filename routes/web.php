<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\ReferralLandingController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TrackingRedirectController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Trang công khai
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');
Route::get('/ma-giam-gia', [VoucherController::class, 'index'])->name('vouchers.index');
Route::get('/ma-giam-gia/{platform:code}', [VoucherController::class, 'platform'])->name('vouchers.platform');
Route::get('/ty-le-hoan-tien', RateController::class)->name('rates');
Route::get('/blog', [PostController::class, 'index'])->name('posts.index');
Route::get('/blog/danh-muc/{category:slug}', [PostController::class, 'category'])->name('posts.category');
Route::get('/blog/{post:slug}', [PostController::class, 'show'])->name('posts.show');
Route::view('/huong-dan', 'pages.guide')->name('pages.guide');
Route::view('/cau-hoi-thuong-gap', 'pages.faq')->name('pages.faq');
Route::view('/dieu-khoan', 'pages.terms')->name('pages.terms');
Route::view('/chinh-sach-bao-mat', 'pages.privacy')->name('pages.privacy');

// Không cần session/cookie: bot đọc nhanh, không tạo session rác, lỗi DB session không làm hỏng robots.txt
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->withoutMiddleware('web')->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->withoutMiddleware('web')->name('robots');

Route::get('/r/{code}', ReferralLandingController::class)->name('referral.landing');
Route::get('/go/{subId}', TrackingRedirectController::class)->name('links.redirect');

// Telegram gọi trực tiếp, xác thực bằng header secret thay cho session/CSRF; không throttle theo IP vì mọi tin đều đến từ IP của Telegram
Route::post('/telegram/webhook', TelegramWebhookController::class)->withoutMiddleware('web')->name('telegram.webhook');

/*
|--------------------------------------------------------------------------
| Đăng nhập / đăng ký
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/dang-nhap', [LoginController::class, 'create'])->name('login');
    Route::post('/dang-nhap', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/dang-ky', [RegisterController::class, 'create'])->name('register');
    Route::post('/dang-ky', [RegisterController::class, 'store'])->middleware('throttle:5,1');

    Route::get('/quen-mat-khau', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/quen-mat-khau', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/dat-lai-mat-khau/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/dat-lai-mat-khau', [PasswordResetController::class, 'update'])->name('password.update');

    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->whereIn('provider', SocialLoginController::PROVIDERS)->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->whereIn('provider', SocialLoginController::PROVIDERS)->name('social.callback');
});

Route::post('/dang-xuat', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('xac-thuc-email')->name('verification.')->group(function () {
    Route::get('/', [EmailVerificationController::class, 'notice'])->name('notice');
    Route::get('/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verify');
    Route::post('/gui-lai', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('send');
});

/*
|--------------------------------------------------------------------------
| Tài khoản thành viên
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'verified'])->prefix('tai-khoan')->name('dashboard.')->group(function () {
    Route::get('/', Dashboard\OverviewController::class)->name('index');

    Route::get('/link-hoan-tien', [Dashboard\TrackingLinkController::class, 'index'])->name('links.index');
    Route::get('/chia-se', Dashboard\ShareTargetController::class)->name('share-target');

    Route::get('/don-hang', [Dashboard\OrderController::class, 'index'])->name('orders.index');
    Route::get('/don-hang/{order}', [Dashboard\OrderController::class, 'show'])->name('orders.show');

    Route::get('/bien-dong-so-du', Dashboard\WalletController::class)->name('wallet.index');

    Route::get('/rut-tien', [Dashboard\WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/rut-tien/{withdrawal}/huy', [Dashboard\WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');

    Route::get('/ngan-hang', [Dashboard\BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('/ngan-hang', [Dashboard\BankAccountController::class, 'store'])->middleware('throttle:10,1')->name('bank-accounts.store');
    Route::post('/ngan-hang/{bankAccount}/mac-dinh', [Dashboard\BankAccountController::class, 'makeDefault'])->name('bank-accounts.default');
    Route::delete('/ngan-hang/{bankAccount}', [Dashboard\BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    Route::get('/gioi-thieu', Dashboard\ReferralController::class)->name('referrals.index');
    Route::get('/diem-danh', Dashboard\CheckinController::class)->name('checkins.index');

    Route::get('/thong-bao', [Dashboard\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/thong-bao/{id}', [Dashboard\NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/thong-bao/doc-tat-ca', [Dashboard\NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/san-pham-da-luu', [Dashboard\SavedProductController::class, 'index'])->name('saved-products.index');
    Route::post('/san-pham-da-luu', [Dashboard\SavedProductController::class, 'store'])->name('saved-products.store');
    Route::delete('/san-pham-da-luu/{savedProduct}', [Dashboard\SavedProductController::class, 'destroy'])->name('saved-products.destroy');

    Route::get('/ho-tro', [Dashboard\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/ho-tro/tao-moi', [Dashboard\TicketController::class, 'create'])->name('tickets.create');
    Route::post('/ho-tro', [Dashboard\TicketController::class, 'store'])->middleware('throttle:5,1')->name('tickets.store');
    Route::get('/ho-tro/{ticket}', [Dashboard\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/ho-tro/{ticket}/tra-loi', [Dashboard\TicketController::class, 'reply'])->middleware('throttle:10,1')->name('tickets.reply');

    Route::get('/ho-so', [Dashboard\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/ho-so', [Dashboard\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/ho-so/mat-khau', [Dashboard\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/ho-so/telegram', [Dashboard\TelegramLinkController::class, 'store'])->middleware('throttle:10,1')->name('telegram.link');
    Route::delete('/ho-so/telegram', [Dashboard\TelegramLinkController::class, 'destroy'])->name('telegram.unlink');
});
