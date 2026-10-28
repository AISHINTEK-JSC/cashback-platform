@props(['title', 'description' => null])

@php
    $user = auth()->user();
    $unread = $user->unreadNotifications()->count();
    $groups = [
        'Hoàn tiền' => [
            ['route' => 'dashboard.index', 'match' => 'dashboard.index', 'label' => 'Tổng quan', 'icon' => 'home'],
            ['route' => 'dashboard.links.index', 'match' => 'dashboard.links.*', 'label' => 'Link hoàn tiền', 'icon' => 'link'],
            ['route' => 'dashboard.orders.index', 'match' => 'dashboard.orders.*', 'label' => 'Đơn hàng hoàn tiền', 'icon' => 'shopping-bag'],
        ],
        'Ví tiền' => [
            ['route' => 'dashboard.wallet.index', 'match' => 'dashboard.wallet.*', 'label' => 'Biến động số dư', 'icon' => 'queue-list'],
            ['route' => 'dashboard.withdrawals.index', 'match' => 'dashboard.withdrawals.*', 'label' => 'Rút tiền', 'icon' => 'banknotes'],
            ['route' => 'dashboard.bank-accounts.index', 'match' => 'dashboard.bank-accounts.*', 'label' => 'Tài khoản nhận tiền', 'icon' => 'building-library'],
        ],
        'Ưu đãi' => [
            ['route' => 'dashboard.referrals.index', 'match' => 'dashboard.referrals.*', 'label' => 'Giới thiệu bạn bè', 'icon' => 'users'],
            ['route' => 'dashboard.checkins.index', 'match' => 'dashboard.checkins.*', 'label' => 'Điểm danh', 'icon' => 'calendar-days'],
            ['route' => 'dashboard.saved-products.index', 'match' => 'dashboard.saved-products.*', 'label' => 'Sản phẩm đã lưu', 'icon' => 'heart'],
        ],
        'Tài khoản' => [
            ['route' => 'dashboard.notifications.index', 'match' => 'dashboard.notifications.*', 'label' => 'Thông báo', 'icon' => 'bell', 'count' => $unread],
            ['route' => 'dashboard.tickets.index', 'match' => 'dashboard.tickets.*', 'label' => 'Hỗ trợ / khiếu nại', 'icon' => 'lifebuoy'],
            ['route' => 'dashboard.profile.edit', 'match' => 'dashboard.profile.*', 'label' => 'Hồ sơ & bảo mật', 'icon' => 'user-circle'],
        ],
    ];
@endphp

<x-layouts.app :title="$title" noindex>
    <div class="container-page py-6 lg:py-10">
        <div class="lg:grid lg:grid-cols-[248px_1fr] lg:gap-8">
            <aside class="mb-6 lg:mb-0">
                <div class="lg:sticky lg:top-24 lg:space-y-4">
                    <a href="{{ route('dashboard.profile.edit') }}" class="card hidden items-center gap-3 p-3 transition hover:border-stone-300 lg:flex lg:shadow-md">
                        <span class="grid size-11 shrink-0 place-items-center overflow-hidden rounded-full bg-brand-100 font-semibold text-brand-700">
                            @if ($user->avatar)
                                <img src="{{ $user->avatar }}" alt="" class="size-full object-cover">
                            @else
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-stone-900">{{ $user->name }}</span>
                            <span class="flex items-center gap-1 text-xs text-stone-500">
                                <x-heroicon-s-star class="size-3.5 text-amber-400" />
                                {{ $user->level?->name ?? 'Thành viên mới' }}
                            </span>
                        </span>
                    </a>

                    {{-- Desktop: menu nằm trong khung trắng đổ bóng; mobile giữ dạng thanh cuộn ngang --}}
                    <nav class="-mx-4 flex gap-1.5 overflow-x-auto px-4 pb-2 [scrollbar-width:none] lg:mx-0 lg:block lg:space-y-5 lg:overflow-visible lg:rounded-2xl lg:border lg:border-stone-200 lg:bg-surface lg:p-3 lg:shadow-md" aria-label="Menu tài khoản">
                        @foreach ($groups as $group => $items)
                            <div class="contents lg:block">
                                <p class="mb-1.5 hidden px-3 text-[11px] font-semibold tracking-wider text-stone-400 uppercase lg:block">{{ $group }}</p>
                                <div class="contents lg:block lg:space-y-0.5">
                                    @foreach ($items as $item)
                                        @php $isActive = request()->routeIs($item['match']); @endphp
                                        <a href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif @class([
                                            'group relative flex shrink-0 items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium whitespace-nowrap transition',
                                            'bg-brand-600 text-white shadow-sm lg:bg-brand-50 lg:text-brand-700 lg:shadow-none' => $isActive,
                                            'border border-stone-200 bg-surface text-stone-600 hover:text-stone-900 lg:border-0 lg:bg-transparent lg:hover:bg-stone-100' => ! $isActive,
                                        ])>
                                            @if ($isActive)
                                                <span class="absolute inset-y-2 -left-px hidden w-1 rounded-r-full bg-brand-600 lg:block"></span>
                                            @endif
                                            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" @class([
                                                'size-5 shrink-0',
                                                'text-stone-400 group-hover:text-stone-600' => ! $isActive,
                                            ]) />
                                            <span class="lg:flex-1">{{ $item['label'] }}</span>
                                            @if (($item['count'] ?? 0) > 0)
                                                <span @class([
                                                    'grid min-w-5 place-items-center rounded-full px-1.5 text-[11px] font-bold tabular-nums',
                                                    'bg-white text-brand-600 lg:bg-brand-600 lg:text-white' => $isActive,
                                                    'bg-brand-600 text-white' => ! $isActive,
                                                ])>{{ $item['count'] > 99 ? '99+' : $item['count'] }}</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <form method="POST" action="{{ route('logout') }}" class="shrink-0 lg:border-t lg:border-stone-200 lg:pt-4">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl border border-stone-200 bg-surface px-3 py-2 text-sm font-medium whitespace-nowrap text-stone-600 transition hover:text-red-600 lg:border-0 lg:bg-transparent lg:hover:bg-red-50">
                                <x-heroicon-o-arrow-right-start-on-rectangle class="size-5" />
                                Đăng xuất
                            </button>
                        </form>
                    </nav>
                </div>
            </aside>

            <div class="min-w-0">
                <x-flash />
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-bold tracking-tight text-stone-900">{{ $title }}</h1>
                        @if ($description)
                            <p class="mt-1 text-sm text-stone-500">{{ $description }}</p>
                        @endif
                    </div>
                    @isset($actions)
                        <div class="flex shrink-0 flex-wrap gap-2">{{ $actions }}</div>
                    @endisset
                </div>
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.app>
