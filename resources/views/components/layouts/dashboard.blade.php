@props(['title'])

@php
    $items = [
        ['route' => 'dashboard.index', 'match' => 'dashboard.index', 'label' => 'Tổng quan', 'icon' => 'home'],
        ['route' => 'dashboard.links.index', 'match' => 'dashboard.links.*', 'label' => 'Link hoàn tiền', 'icon' => 'link'],
        ['route' => 'dashboard.orders.index', 'match' => 'dashboard.orders.*', 'label' => 'Đơn hàng hoàn tiền', 'icon' => 'shopping-bag'],
        ['route' => 'dashboard.wallet.index', 'match' => 'dashboard.wallet.*', 'label' => 'Biến động số dư', 'icon' => 'queue-list'],
        ['route' => 'dashboard.withdrawals.index', 'match' => 'dashboard.withdrawals.*', 'label' => 'Rút tiền', 'icon' => 'banknotes'],
        ['route' => 'dashboard.bank-accounts.index', 'match' => 'dashboard.bank-accounts.*', 'label' => 'Tài khoản nhận tiền', 'icon' => 'building-library'],
        ['route' => 'dashboard.referrals.index', 'match' => 'dashboard.referrals.*', 'label' => 'Giới thiệu bạn bè', 'icon' => 'users'],
        ['route' => 'dashboard.checkins.index', 'match' => 'dashboard.checkins.*', 'label' => 'Điểm danh', 'icon' => 'calendar-days'],
        ['route' => 'dashboard.saved-products.index', 'match' => 'dashboard.saved-products.*', 'label' => 'Sản phẩm đã lưu', 'icon' => 'heart'],
        ['route' => 'dashboard.notifications.index', 'match' => 'dashboard.notifications.*', 'label' => 'Thông báo', 'icon' => 'bell'],
        ['route' => 'dashboard.tickets.index', 'match' => 'dashboard.tickets.*', 'label' => 'Hỗ trợ / khiếu nại', 'icon' => 'lifebuoy'],
        ['route' => 'dashboard.profile.edit', 'match' => 'dashboard.profile.*', 'label' => 'Hồ sơ & bảo mật', 'icon' => 'user-circle'],
    ];
@endphp

<x-layouts.app :title="$title">
    <div class="container-page py-6 lg:py-10">
        <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-8">
            <aside class="mb-6 lg:mb-0">
                <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 pb-2 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0">
                    @foreach ($items as $item)
                        <a href="{{ route($item['route']) }}" @class([
                            'flex shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium whitespace-nowrap transition',
                            'bg-brand-600 text-white shadow-sm' => request()->routeIs($item['match']),
                            'text-stone-600 hover:bg-white hover:text-stone-900' => ! request()->routeIs($item['match']),
                        ])>
                            <x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-5 shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium whitespace-nowrap text-stone-600 hover:bg-white hover:text-red-600">
                            <x-heroicon-o-arrow-right-start-on-rectangle class="size-5" />
                            Đăng xuất
                        </button>
                    </form>
                </nav>
            </aside>

            <div class="min-w-0">
                <x-flash />
                <h1 class="mb-6 text-2xl font-bold text-stone-900">{{ $title }}</h1>
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.app>
