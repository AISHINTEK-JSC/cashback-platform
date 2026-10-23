<x-layouts.dashboard title="Đơn hàng hoàn tiền" description="Đơn mua qua link hoàn tiền thường xuất hiện sau vài giờ.">
    <x-slot:actions>
        <a href="{{ route('dashboard.tickets.create', ['type' => 'missing_order']) }}" class="btn btn-secondary">
            <x-heroicon-o-exclamation-circle class="size-5" /> Khiếu nại đơn thiếu
        </a>
    </x-slot:actions>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Confirmed, \App\Enums\OrderStatus::Approved, \App\Enums\OrderStatus::Cancelled] as $status)
            @php $isSelected = ($filters['trang_thai'] ?? null) === $status->value; @endphp
            <a href="{{ route('dashboard.orders.index', $isSelected ? [] : ['trang_thai' => $status->value]) }}" @class([
                'card p-4 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md',
                '!border-brand-500 ring-2 ring-brand-500/20' => $isSelected,
            ]) @if ($isSelected) aria-current="true" @endif>
                <div class="flex items-center justify-between gap-2">
                    <x-status-badge :status="$status" />
                    <span class="text-xs text-stone-500 tabular-nums">{{ (int) ($totals[$status->value]->orders_count ?? 0) }} đơn</span>
                </div>
                <p class="mt-3 text-xl font-bold tracking-tight tabular-nums">{{ vnd((int) ($totals[$status->value]->cashback_total ?? 0)) }}</p>
                <p class="text-xs text-stone-500">tiền hoàn</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="card mt-6 flex flex-col gap-2 p-3 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-stone-400" />
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm mã đơn hàng" class="input !pl-11" aria-label="Tìm mã đơn hàng">
        </div>
        <select name="san" class="input sm:max-w-44" aria-label="Sàn">
            <option value="">Tất cả sàn</option>
            @foreach ($platforms as $platform)
                <option value="{{ $platform->code }}" @selected(($filters['san'] ?? null) === $platform->code)>{{ $platform->name }}</option>
            @endforeach
        </select>
        <select name="trang_thai" class="input sm:max-w-44" aria-label="Trạng thái">
            <option value="">Tất cả trạng thái</option>
            @foreach (\App\Enums\OrderStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['trang_thai'] ?? null) === $status->value)>{{ $status->getLabel() }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary flex-1"><x-heroicon-o-funnel class="size-4" /> Lọc</button>
            @if (array_filter($filters ?? []))
                <a href="{{ route('dashboard.orders.index') }}" class="btn btn-ghost">Xoá lọc</a>
            @endif
        </div>
    </form>

    <div class="mt-4">
        @include('dashboard.orders.table', ['orders' => $orders])
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
</x-layouts.dashboard>
