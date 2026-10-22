<x-layouts.dashboard title="Đơn hàng hoàn tiền">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Confirmed, \App\Enums\OrderStatus::Approved, \App\Enums\OrderStatus::Cancelled] as $status)
            <a href="{{ route('dashboard.orders.index', ['trang_thai' => $status->value]) }}" @class([
                'card p-4 transition hover:border-brand-300',
                '!border-brand-500 ring-2 ring-brand-500/20' => ($filters['trang_thai'] ?? null) === $status->value,
            ])>
                <x-status-badge :status="$status" />
                <p class="mt-2 text-lg font-bold tabular-nums">{{ vnd((int) ($totals[$status->value]->cashback_total ?? 0)) }}</p>
                <p class="text-xs text-stone-500">{{ (int) ($totals[$status->value]->orders_count ?? 0) }} đơn</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="mt-6 flex flex-col gap-2 sm:flex-row">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tìm mã đơn hàng" class="input sm:max-w-xs">
        <select name="san" class="input sm:max-w-44">
            <option value="">Tất cả sàn</option>
            @foreach ($platforms as $platform)
                <option value="{{ $platform->code }}" @selected(($filters['san'] ?? null) === $platform->code)>{{ $platform->name }}</option>
            @endforeach
        </select>
        <select name="trang_thai" class="input sm:max-w-44">
            <option value="">Tất cả trạng thái</option>
            @foreach (\App\Enums\OrderStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['trang_thai'] ?? null) === $status->value)>{{ $status->getLabel() }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Lọc</button>
    </form>

    <div class="mt-4">
        @include('dashboard.orders.table', ['orders' => $orders])
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>

    <p class="mt-6 text-sm text-stone-600">
        Mua qua link nhưng không thấy đơn sau 24 giờ?
        <a href="{{ route('dashboard.tickets.create', ['type' => 'missing_order']) }}" class="font-semibold text-brand-700 underline">Khiếu nại đơn thiếu</a>
    </p>
</x-layouts.dashboard>
