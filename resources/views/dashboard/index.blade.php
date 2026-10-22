<x-layouts.dashboard title="Tổng quan">
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Có thể rút" :value="vnd($wallet->available_balance)" tone="brand" />
        <x-stat-card label="Chờ duyệt" :value="vnd($wallet->pending_balance)" hint="Chuyển sang có thể rút khi sàn chốt đơn" />
        <x-stat-card label="Tổng đã nhận" :value="vnd($wallet->total_earned)" :hint="'Đã rút '.vnd($wallet->total_withdrawn)" />
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
        <a href="{{ route('dashboard.withdrawals.index') }}" class="btn btn-primary"><x-heroicon-o-banknotes class="size-5" /> Rút tiền</a>
        <a href="{{ route('dashboard.wallet.index') }}" class="btn btn-secondary">Biến động số dư</a>
    </div>

    <div class="card mt-6 p-5">
        <p class="mb-3 font-semibold text-stone-900">Lấy link hoàn tiền</p>
        <livewire:link-generator />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <livewire:checkin-widget />

        <div class="card p-5">
            <p class="font-semibold text-stone-900">Hạng thành viên</p>
            <p class="mt-1 text-2xl font-bold text-brand-600">{{ $user->level?->name ?? 'Thành viên mới' }}</p>
            @if ($user->level && (float) $user->level->bonus_rate > 0)
                <p class="text-sm text-stone-600">Cộng thêm {{ rtrim(rtrim(number_format((float) $user->level->bonus_rate, 2, ',', '.'), '0'), ',') }}% trên mỗi đơn</p>
            @endif
            @if ($nextLevel)
                @php $progress = min(100, (int) round($approvedOrders / max(1, $nextLevel->min_approved_orders) * 100)); @endphp
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-stone-100">
                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $progress }}%"></div>
                </div>
                <p class="mt-2 text-xs text-stone-500">{{ $approvedOrders }}/{{ $nextLevel->min_approved_orders }} đơn được duyệt để lên hạng {{ $nextLevel->name }}</p>
            @endif
        </div>
    </div>

    <div class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-semibold text-stone-900">Đơn hàng gần đây</h2>
            <a href="{{ route('dashboard.orders.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Xem tất cả</a>
        </div>
        @include('dashboard.orders.table', ['orders' => $recentOrders])
    </div>
</x-layouts.dashboard>
