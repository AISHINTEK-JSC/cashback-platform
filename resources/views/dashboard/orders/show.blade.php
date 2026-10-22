@php
    $cancelled = $order->status === \App\Enums\OrderStatus::Cancelled;
    $available = in_array($order->status, [\App\Enums\OrderStatus::Approved, \App\Enums\OrderStatus::Paid], true);
    $steps = [
        ['label' => 'Ngày mua', 'at' => $order->purchased_at, 'done' => true],
        ['label' => 'Ghi nhận', 'at' => $order->created_at, 'done' => true],
        ['label' => $cancelled ? 'Đã huỷ' : 'Có thể rút', 'at' => $cancelled ? $order->cancelled_at : ($available ? $order->approved_at : $order->expectedAvailableAt()), 'done' => $available || $cancelled],
    ];
@endphp

<x-layouts.dashboard :title="'Đơn '.$order->platform_order_id">
    <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
        <div class="space-y-6">
            <div class="card p-5">
                <div class="flex flex-wrap items-center gap-2">
                    <x-platform-badge :platform="$order->platform" />
                    <x-status-badge :status="$order->status" />
                    @if ($order->platform_status)
                        <span class="text-xs text-stone-500">Sàn: {{ $order->platform_status }}</span>
                    @endif
                </div>

                <ol class="mt-6 grid gap-4 sm:grid-cols-3">
                    @foreach ($steps as $i => $step)
                        <li class="flex gap-3">
                            <span @class([
                                'grid size-8 shrink-0 place-items-center rounded-full text-sm font-bold',
                                'bg-red-600 text-white' => $cancelled && $i === 2,
                                'bg-brand-600 text-white' => $step['done'] && ! ($cancelled && $i === 2),
                                'bg-stone-200 text-stone-500' => ! $step['done'],
                            ])>{{ $i + 1 }}</span>
                            <div>
                                <p class="font-semibold text-stone-900">{{ $step['label'] }}</p>
                                <p class="text-sm text-stone-500">
                                    @if ($step['at'])
                                        {{ $step['done'] ? '' : 'Dự kiến ' }}{{ $step['at']->format('d/m/Y') }}
                                    @else
                                        -
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="card overflow-x-auto">
                <table class="table-base min-w-[520px]">
                    <thead><tr><th>Sản phẩm</th><th class="text-right">SL</th><th class="text-right">Đơn giá</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                        @forelse ($order->items as $item)
                            <tr>
                                <td class="font-medium text-stone-900">{{ $item->item_name ?? $item->item_id }}</td>
                                <td class="text-right">{{ $item->quantity }}</td>
                                <td class="text-right tabular-nums">{{ vnd($item->price) }}</td>
                                <td class="text-stone-600">{{ $item->status ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-stone-500">Sàn chưa cung cấp chi tiết sản phẩm.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card p-5">
                <p class="font-semibold text-stone-900">Lịch sử trạng thái</p>
                <ul class="mt-3 space-y-3">
                    @foreach ($order->statusHistories as $history)
                        <li class="flex gap-3 text-sm">
                            <x-heroicon-o-clock class="mt-0.5 size-4 shrink-0 text-stone-400" />
                            <div>
                                <p class="text-stone-900">{{ \App\Enums\OrderStatus::tryFrom($history->to_status)?->getLabel() ?? $history->to_status }}@if ($history->note) <span class="text-stone-500">- {{ $history->note }}</span>@endif</p>
                                <p class="text-xs text-stone-500">{{ $history->created_at?->format('d/m/Y H:i') }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-stone-500">Giá trị đơn</dt><dd class="font-medium tabular-nums">{{ vnd($order->order_amount) }}</dd></div>
                    <div class="flex justify-between border-t border-stone-100 pt-3"><dt class="text-stone-500">Tiền hoàn của bạn</dt><dd class="text-lg font-bold text-emerald-700 tabular-nums">{{ vnd($order->cashback_amount) }}</dd></div>
                </dl>
            </div>
            @if ($order->trackingLink)
                <a href="{{ $order->trackingLink->original_url }}" target="_blank" rel="noopener" class="btn btn-secondary w-full">Xem sản phẩm gốc</a>
            @endif
            <a href="{{ route('dashboard.tickets.create', ['type' => 'wrong_amount', 'platform_order_id' => $order->platform_order_id]) }}" class="btn btn-ghost w-full">Báo sai số tiền</a>
        </aside>
    </div>
</x-layouts.dashboard>
