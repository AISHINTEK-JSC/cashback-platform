{{--
    Trang bảo trì (php artisan down). Không dùng layout chung vì layout đọc setting()/user từ DB,
    mà lúc bảo trì DB có thể đang migrate hoặc không kết nối được.
--}}
@php
    $siteName = config('app.name');
    $retryAfter = isset($exception) ? ($exception->getHeaders()['Retry-After'] ?? null) : null;
    $retryMinutes = is_numeric($retryAfter) ? max(1, (int) ceil($retryAfter / 60)) : null;
    $hotline = rescue(fn () => setting('support_hotline'), report: false);
    $zalo = rescue(fn () => setting('support_zalo'), report: false);
@endphp

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta http-equiv="refresh" content="{{ is_numeric($retryAfter) ? max(30, (int) $retryAfter) : 60 }}">
    <title>Đang bảo trì - {{ $siteName }}</title>
    <meta name="theme-color" content="#e04412" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#17120f" media="(prefers-color-scheme: dark)">
    @include('partials.favicons')
    <script>
        (() => {
            let theme = null;
            try { theme = localStorage.getItem('theme'); } catch {}
            const isDark = theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-gradient-to-b from-brand-50 to-stone-50">
    <header class="container-page flex h-16 items-center">
        <x-logo :name="$siteName" />
    </header>

    <main class="container-page flex flex-1 flex-col items-center justify-center py-12 text-center">
        <span class="relative grid size-24 place-items-center rounded-3xl bg-surface text-brand-600 shadow-sm ring-1 ring-stone-200">
            <x-heroicon-o-wrench-screwdriver class="size-11" />
            <span class="absolute -top-1.5 -right-1.5 flex size-5">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-400 opacity-75"></span>
                <span class="relative inline-flex size-5 rounded-full bg-brand-500 ring-4 ring-surface"></span>
            </span>
        </span>

        <p class="mt-8 inline-flex items-center gap-2 rounded-full bg-surface px-3 py-1 text-sm font-medium text-brand-700 ring-1 ring-brand-200">
            <x-heroicon-o-clock class="size-4" />
            @if ($retryMinutes)
                Dự kiến hoạt động lại sau khoảng {{ $retryMinutes }} phút
            @else
                Sẽ hoạt động lại trong ít phút
            @endif
        </p>

        <h1 class="mt-5 max-w-2xl text-3xl font-bold tracking-tight text-balance text-stone-900 sm:text-4xl">
            Hệ thống đang <span class="text-brand-600">bảo trì</span>
        </h1>
        <p class="mt-3 max-w-xl text-stone-600">Chúng tôi đang nâng cấp để {{ $siteName }} chạy nhanh và ổn định hơn. Đơn hàng, tiền hoàn và số dư trong ví của bạn vẫn được giữ nguyên.</p>

        <ul class="mt-8 grid w-full max-w-2xl gap-3 text-left sm:grid-cols-3">
            <li class="card flex items-start gap-3 p-4">
                <x-heroicon-o-shield-check class="size-5 shrink-0 text-brand-600" />
                <span class="text-sm text-stone-600"><span class="block font-semibold text-stone-900">Ví an toàn</span>Số dư không bị ảnh hưởng</span>
            </li>
            <li class="card flex items-start gap-3 p-4">
                <x-heroicon-o-shopping-bag class="size-5 shrink-0 text-brand-600" />
                <span class="text-sm text-stone-600"><span class="block font-semibold text-stone-900">Đơn vẫn ghi nhận</span>Đơn mua qua link hoàn tiền cũ</span>
            </li>
            <li class="card flex items-start gap-3 p-4">
                <x-heroicon-o-arrow-path class="size-5 shrink-0 text-brand-600" />
                <span class="text-sm text-stone-600"><span class="block font-semibold text-stone-900">Tự tải lại</span>Trang tự mở khi xong bảo trì</span>
            </li>
        </ul>

        <button type="button" onclick="location.reload()" class="btn btn-primary mt-8">
            <x-heroicon-o-arrow-path class="size-5" /> Thử lại
        </button>
    </main>

    <footer class="container-page flex flex-wrap items-center justify-center gap-x-4 gap-y-1 py-6 text-sm text-stone-500">
        <span>© {{ now()->year }} {{ $siteName }}</span>
        @if ($hotline)
            <span>Hotline: <a href="tel:{{ $hotline }}" class="font-medium text-stone-900">{{ $hotline }}</a></span>
        @endif
        @if ($zalo)
            <a href="{{ $zalo }}" class="font-medium text-stone-900" target="_blank" rel="noopener">Zalo hỗ trợ</a>
        @endif
    </footer>
</body>
</html>
