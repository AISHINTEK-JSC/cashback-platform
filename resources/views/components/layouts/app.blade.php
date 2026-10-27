@props(['title' => null, 'description' => null, 'image' => null, 'type' => 'website', 'noindex' => false])

@php
    $siteName = setting('site_name');
    $pageTitle = $title ? $title.' - '.$siteName : $siteName.' - Hoàn tiền khi mua Shopee, TikTok Shop, Lazada';
    if (request()->integer('page') > 1) {
        $pageTitle = ($title ?: $siteName).' - Trang '.request()->integer('page').' - '.$siteName;
    }
    $pageDescription = $description ?: $siteName.': dán link sản phẩm Shopee, TikTok Shop, Lazada, mua như bình thường và nhận lại tiền hoàn cho mỗi đơn hàng, rút tiền về ngân hàng.';
    $shareImage = $image ? url($image) : asset('images/og-default.png');
    $canonical = request()->integer('page') > 1 ? url()->current().'?page='.request()->integer('page') : url()->current();
    $user = auth()->user();
    $unread = $user?->unreadNotifications()->count() ?? 0;
    $nav = [
        ['route' => 'home', 'label' => 'Trang chủ'],
        ['route' => 'vouchers.index', 'label' => 'Mã giảm giá'],
        ['route' => 'rates', 'label' => 'Tỷ lệ hoàn tiền'],
        ['route' => 'pages.guide', 'label' => 'Hướng dẫn'],
        ['route' => 'posts.index', 'label' => 'Blog'],
    ];
@endphp

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="{{ $noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1' }}">
    <meta name="application-name" content="{{ $siteName }}">
    <meta name="apple-mobile-web-app-title" content="{{ $siteName }}">
    <meta name="theme-color" content="#e04412" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#17120f" media="(prefers-color-scheme: dark)">
    @include('partials.favicons')
    <link rel="manifest" href="/site.webmanifest">

    {{-- Thẻ chia sẻ cho Facebook, Zalo, Messenger, X --}}
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $title ?: $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:image:alt" content="{{ $title ?: $siteName }}">
    @unless ($image)
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
    @endunless
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?: $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $shareImage }}">
    <meta name="twitter:image:alt" content="{{ $title ?: $siteName }}">
    @if ($siteVerification = setting('google_site_verification'))
        <meta name="google-site-verification" content="{{ $siteVerification }}">
    @endif
    {{-- DB dùng chung với local nên chỉ đo ở production để số liệu GA không lẫn lượt xem khi dev --}}
    @if (app()->isProduction() && ($analyticsId = setting('google_analytics_id')))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analyticsId }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', @js($analyticsId));
            window.addEventListener('cashback-link-created', (event) => gtag('event', 'generate_link', { platform: event.detail.platform }));
        </script>
    @endif
    {{-- Áp theme trước khi vẽ trang để không bị chớp sáng; dùng chung khoá "theme" với Filament --}}
    <script>
        (() => {
            let theme = null;
            try { theme = localStorage.getItem('theme'); } catch {}
            const isDark = theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    {{ $head ?? '' }}
</head>
<body class="flex min-h-screen flex-col">
    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-stone-200 bg-surface/90 backdrop-blur">
        <div class="container-page flex h-16 items-center gap-3 lg:gap-6">
            <a href="{{ route('home') }}" aria-label="{{ $siteName }} - Trang chủ">
                <x-logo :name="$siteName" />
            </a>

            <nav class="hidden flex-1 items-center gap-1 lg:flex">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" @class([
                        'rounded-lg px-3 py-2 text-sm font-medium transition',
                        'text-brand-700 bg-brand-50' => request()->routeIs($item['route']),
                        'text-stone-600 hover:text-stone-900 hover:bg-stone-100' => ! request()->routeIs($item['route']),
                    ])>{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <button type="button" x-data="themeToggle" @click="toggle()" class="btn btn-ghost !px-2.5" :aria-label="isDark ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối'" :title="isDark ? 'Giao diện sáng' : 'Giao diện tối'" aria-label="Đổi giao diện sáng/tối">
                    <x-heroicon-o-moon class="size-5" x-show="!isDark" />
                    <x-heroicon-o-sun class="size-5" x-show="isDark" x-cloak />
                </button>
                @auth
                    <a href="{{ route('dashboard.notifications.index') }}" class="btn btn-ghost relative !px-2.5" aria-label="Thông báo">
                        <x-heroicon-o-bell class="size-5" />
                        @if ($unread > 0)
                            <span class="absolute -top-0.5 -right-0.5 grid min-w-5 place-items-center rounded-full bg-brand-600 px-1 text-[11px] font-bold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
                        @endif
                    </a>
                    <a href="{{ route('dashboard.index') }}" class="btn btn-secondary hidden sm:inline-flex">
                        <x-heroicon-o-wallet class="size-5 text-brand-600" />
                        {{ vnd($user->wallet?->available_balance) }}
                    </a>
                    <a href="{{ route('dashboard.index') }}" class="grid size-10 place-items-center overflow-hidden rounded-full bg-stone-200 font-semibold text-stone-700" aria-label="Tài khoản">
                        @if ($user->avatar)
                            <img src="{{ $user->avatar }}" alt="" class="size-full object-cover">
                        @else
                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                        @endif
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost hidden sm:inline-flex">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="btn btn-primary whitespace-nowrap">Đăng ký</a>
                @endauth

                <button type="button" class="btn btn-ghost !px-2.5 lg:hidden" @click="open = !open" aria-label="Menu">
                    <x-heroicon-o-bars-3 class="size-6" x-show="!open" />
                    <x-heroicon-o-x-mark class="size-6" x-show="open" x-cloak />
                </button>
            </div>
        </div>

        <nav x-show="open" x-cloak x-transition class="border-t border-stone-200 bg-surface lg:hidden">
            <div class="container-page flex flex-col py-2">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="rounded-lg px-3 py-2.5 font-medium text-stone-700 hover:bg-stone-100">{{ $item['label'] }}</a>
                @endforeach
                @guest
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2.5 font-medium text-stone-700 hover:bg-stone-100">Đăng nhập</a>
                @endguest
                <button type="button" x-data="pwaInstall" x-show="canInstall" x-cloak @click="install()" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-left font-medium text-brand-700 hover:bg-stone-100">
                    <x-heroicon-o-arrow-down-tray class="size-5" /> Cài ứng dụng
                </button>
            </div>
        </nav>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-stone-200 bg-surface">
        <div class="container-page grid gap-8 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <x-logo :name="$siteName" />
                <p class="mt-2 max-w-md text-sm text-stone-600">Nền tảng hoàn tiền khi mua sắm trên sàn thương mại điện tử và website thương hiệu. Chúng tôi nhận hoa hồng tiếp thị liên kết từ sàn và chia lại phần lớn cho bạn.</p>
                <div class="mt-4 flex flex-wrap gap-3 text-sm text-stone-600">
                    @if ($hotline = setting('support_hotline'))
                        <span>Hotline: <a href="tel:{{ $hotline }}" class="font-medium text-stone-900">{{ $hotline }}</a></span>
                    @endif
                    @if ($zalo = setting('support_zalo'))
                        <a href="{{ $zalo }}" class="font-medium text-stone-900" target="_blank" rel="noopener">Zalo hỗ trợ</a>
                    @endif
                    @if ($facebook = setting('support_facebook'))
                        <a href="{{ $facebook }}" class="font-medium text-stone-900" target="_blank" rel="noopener">Fanpage</a>
                    @endif
                </div>
                <button type="button" x-data="pwaInstall" x-show="canInstall" x-cloak @click="install()" class="btn btn-secondary mt-4">
                    <x-heroicon-o-arrow-down-tray class="size-5 text-brand-600" /> Cài ứng dụng {{ $siteName }}
                </button>
            </div>
            <div>
                <p class="text-sm font-semibold text-stone-900">Khám phá</p>
                <ul class="mt-3 space-y-2 text-sm text-stone-600">
                    <li><a href="{{ route('vouchers.index') }}" class="hover:text-stone-900">Mã giảm giá</a></li>
                    <li><a href="{{ route('rates') }}" class="hover:text-stone-900">Tỷ lệ hoàn tiền</a></li>
                    <li><a href="{{ route('posts.index') }}" class="hover:text-stone-900">Blog mẹo săn sale</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-stone-900">Hỗ trợ</p>
                <ul class="mt-3 space-y-2 text-sm text-stone-600">
                    <li><a href="{{ route('pages.guide') }}" class="hover:text-stone-900">Hướng dẫn nhận hoàn tiền</a></li>
                    <li><a href="{{ route('pages.faq') }}" class="hover:text-stone-900">Câu hỏi thường gặp</a></li>
                    <li><a href="{{ route('pages.terms') }}" class="hover:text-stone-900">Điều khoản sử dụng</a></li>
                    <li><a href="{{ route('pages.privacy') }}" class="hover:text-stone-900">Chính sách bảo mật</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-stone-100 py-4 text-center text-xs text-stone-500">© {{ now()->year }} {{ $siteName }}. Tên sàn và thương hiệu được nhắc đến thuộc về chủ sở hữu tương ứng.</div>
    </footer>

    @livewireScripts
</body>
</html>
