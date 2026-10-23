@props(['title' => null, 'description' => null])

@php
    $siteName = setting('site_name');
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
    <title>{{ $title ? $title.' - '.$siteName : $siteName.' - Hoàn tiền khi mua sắm Shopee, TikTok Shop, Lazada' }}</title>
    <meta name="description" content="{{ $description ?? 'Dán link sản phẩm, mua như bình thường và nhận lại tiền hoàn cho mỗi đơn hàng Shopee, TikTok Shop, Lazada.' }}">
    <meta name="theme-color" content="#e04412">
    <link rel="icon" href="/favicon.ico">
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
        <div class="container-page flex h-16 items-center gap-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold text-stone-900">
                <span class="grid size-9 place-items-center rounded-xl bg-brand-600 text-white">
                    <x-heroicon-s-banknotes class="size-5" />
                </span>
                <span class="text-lg">{{ $siteName }}</span>
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

            <div class="ml-auto flex items-center gap-2">
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
                    <a href="{{ route('register') }}" class="btn btn-primary">Đăng ký</a>
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
            </div>
        </nav>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-stone-200 bg-surface">
        <div class="container-page grid gap-8 py-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <p class="font-bold text-stone-900">{{ $siteName }}</p>
                <p class="mt-2 max-w-md text-sm text-stone-600">Nền tảng hoàn tiền khi mua sắm trên Shopee, TikTok Shop và Lazada. Chúng tôi nhận hoa hồng tiếp thị liên kết từ sàn và chia lại phần lớn cho bạn.</p>
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
        <div class="border-t border-stone-100 py-4 text-center text-xs text-stone-500">© {{ now()->year }} {{ $siteName }}. Shopee, TikTok Shop, Lazada là thương hiệu của các chủ sở hữu tương ứng.</div>
    </footer>

    @livewireScripts
</body>
</html>
