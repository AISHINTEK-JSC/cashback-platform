<x-layouts.dashboard title="Tổng quan" :description="'Xin chào, '.$user->name.'! Đây là tình hình hoàn tiền của bạn.'">
    <div class="grid gap-4 lg:grid-cols-5">
        <div class="relative overflow-hidden rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 dark:to-brand-600 p-6 text-white shadow-lg shadow-brand-600/20 lg:col-span-3">
            <div class="pointer-events-none absolute -top-16 -right-16 size-56 rounded-full bg-white/10"></div>
            <div class="pointer-events-none absolute -right-6 -bottom-24 size-48 rounded-full bg-white/5"></div>

            <div class="relative flex items-center gap-2 text-sm font-medium text-white/80">
                <x-heroicon-o-wallet class="size-5" />
                Số dư có thể rút
            </div>
            <p class="relative mt-2 text-4xl font-bold tracking-tight tabular-nums">{{ vnd($wallet->available_balance) }}</p>

            <div class="relative mt-6 flex flex-wrap gap-2">
                <a href="{{ route('dashboard.withdrawals.index') }}" class="btn bg-white text-brand-600 shadow-sm hover:bg-white/90">
                    <x-heroicon-o-banknotes class="size-5" /> Rút tiền
                </a>
                <a href="{{ route('dashboard.wallet.index') }}" class="btn bg-white/15 text-white ring-1 ring-white/25 hover:bg-white/25">
                    Biến động số dư <x-heroicon-m-arrow-right class="size-4" />
                </a>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2 lg:grid-cols-1">
            <x-stat-card label="Chờ duyệt" icon="clock" :value="vnd($wallet->pending_balance)" hint="Chuyển sang có thể rút khi sàn chốt đơn" />
            <x-stat-card label="Tổng đã nhận" icon="arrow-trending-up" :value="vnd($wallet->total_earned)" :hint="'Đã rút '.vnd($wallet->total_withdrawn)" />
        </div>
    </div>

    <div class="card mt-6 p-5 sm:p-6">
        <div class="mb-4 flex items-start gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">
                <x-heroicon-o-sparkles class="size-5" />
            </span>
            <div>
                <h2 class="font-semibold text-stone-900">Lấy link hoàn tiền</h2>
                <p class="text-sm text-stone-500">Dán link sản phẩm, mở link vừa tạo rồi đặt hàng như bình thường.</p>
            </div>
        </div>
        <livewire:link-generator />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <livewire:checkin-widget />

        <div class="card flex flex-col p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="font-semibold text-stone-900">Hạng thành viên</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-brand-600">{{ $user->level?->name ?? 'Thành viên mới' }}</p>
                </div>
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-500">
                    <x-heroicon-s-trophy class="size-5" />
                </span>
            </div>

            @if ($user->level && (float) $user->level->bonus_rate > 0)
                <p class="mt-1 text-sm text-stone-600">Cộng thêm <span class="font-semibold text-emerald-700">{{ rtrim(rtrim(number_format((float) $user->level->bonus_rate, 2, ',', '.'), '0'), ',') }}%</span> trên mỗi đơn</p>
            @endif

            <div class="mt-auto pt-4">
                @if ($nextLevel)
                    @php $progress = min(100, (int) round($approvedOrders / max(1, $nextLevel->min_approved_orders) * 100)); @endphp
                    <div class="mb-2 flex items-center justify-between text-xs">
                        <span class="text-stone-500">Lên hạng <span class="font-semibold text-stone-700">{{ $nextLevel->name }}</span></span>
                        <span class="font-semibold text-stone-700 tabular-nums">{{ $approvedOrders }}/{{ $nextLevel->min_approved_orders }} đơn</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-stone-100" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="h-full rounded-full bg-linear-to-r from-brand-400 to-brand-600" style="width: {{ $progress }}%"></div>
                    </div>
                @else
                    <p class="text-sm text-stone-500">Có thêm đơn được duyệt để lên hạng và nhận thêm % hoàn tiền.</p>
                @endif
                <a href="{{ route('rates') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-700 hover:underline">
                    Xem quyền lợi các hạng <x-heroicon-m-arrow-right class="size-4" />
                </a>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <x-section-header title="Đơn hàng gần đây">
            @if ($recentOrders->isNotEmpty())
                <a href="{{ route('dashboard.orders.index') }}" class="inline-flex items-center gap-1 font-semibold text-brand-700 hover:underline">Xem tất cả <x-heroicon-m-arrow-right class="size-4" /></a>
            @endif
        </x-section-header>

        @if ($recentOrders->isEmpty())
            <div class="card p-6 sm:p-8">
                <p class="text-center font-semibold text-stone-900">Chưa có đơn hàng nào. Bắt đầu chỉ với 3 bước:</p>
                <ol class="mt-6 grid gap-4 sm:grid-cols-3">
                    @foreach ([
                        ['icon' => 'link', 'title' => 'Dán link sản phẩm', 'body' => 'Copy link từ Shopee, TikTok Shop, Lazada và dán vào ô phía trên.'],
                        ['icon' => 'shopping-cart', 'title' => 'Mua như bình thường', 'body' => 'Mở link hoàn tiền và đặt hàng ngay trong phiên đó.'],
                        ['icon' => 'banknotes', 'title' => 'Nhận tiền hoàn', 'body' => 'Đơn hiện ở đây sau vài giờ, tiền về ví khi sàn chốt đơn.'],
                    ] as $step)
                        <li class="flex gap-3 rounded-xl bg-stone-50 p-4 sm:flex-col">
                            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-surface text-brand-600 shadow-sm ring-1 ring-stone-200">
                                <x-dynamic-component :component="'heroicon-o-'.$step['icon']" class="size-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-stone-900"><span class="text-brand-600">{{ $loop->iteration }}.</span> {{ $step['title'] }}</p>
                                <p class="mt-0.5 text-sm text-stone-500">{{ $step['body'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        @else
            @include('dashboard.orders.table', ['orders' => $recentOrders])
        @endif
    </div>
</x-layouts.dashboard>
