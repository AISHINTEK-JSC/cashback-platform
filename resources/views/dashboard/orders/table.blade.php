@if ($orders->isEmpty())
    <x-empty-state icon="shopping-bag" title="Chưa có đơn hàng nào">
        Đơn mua qua link hoàn tiền sẽ xuất hiện ở đây sau vài giờ.
    </x-empty-state>
@else
    <div class="card overflow-x-auto">
        <table class="table-base min-w-[640px]">
            <thead>
                <tr>
                    <th>Đơn hàng</th>
                    <th>Ngày mua</th>
                    <th class="text-right">Giá trị</th>
                    <th class="text-right">Hoàn tiền</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr class="hover:bg-stone-50" wire:key="order-{{ $order->id }}">
                        <td>
                            <a href="{{ route('dashboard.orders.show', $order) }}" class="font-medium text-stone-900 hover:text-brand-700">{{ $order->platform_order_id }}</a>
                            <div class="mt-1"><x-platform-badge :platform="$order->platform" /></div>
                        </td>
                        <td class="whitespace-nowrap text-stone-600">{{ $order->purchased_at->format('d/m/Y H:i') }}</td>
                        <td class="text-right tabular-nums">{{ vnd($order->order_amount) }}</td>
                        <td @class(['text-right font-semibold tabular-nums', 'text-stone-400 line-through' => $order->status === \App\Enums\OrderStatus::Cancelled, 'text-emerald-700' => $order->status !== \App\Enums\OrderStatus::Cancelled])>{{ vnd($order->cashback_amount) }}</td>
                        <td><x-status-badge :status="$order->status" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
