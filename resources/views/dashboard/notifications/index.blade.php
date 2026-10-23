<x-layouts.dashboard title="Thông báo" description="Cập nhật về đơn hàng, tiền hoàn và yêu cầu rút tiền của bạn.">
    @if ($notifications->isNotEmpty())
        <x-slot:actions>
            <form method="POST" action="{{ route('dashboard.notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn btn-secondary"><x-heroicon-o-check class="size-4" /> Đánh dấu đã đọc tất cả</button>
            </form>
        </x-slot:actions>
    @endif

    @if ($notifications->isEmpty())
        <x-empty-state icon="bell" title="Chưa có thông báo">Bạn sẽ nhận thông báo khi có đơn hàng mới, tiền hoàn được duyệt hoặc rút tiền thành công.</x-empty-state>
    @else
        <div class="card divide-y divide-stone-100 overflow-hidden">
            @foreach ($notifications as $notification)
                <a href="{{ route('dashboard.notifications.show', $notification->id) }}" @class([
                    'flex gap-4 p-4 transition hover:bg-stone-50',
                    'bg-brand-50/40' => ! $notification->read_at,
                ])>
                    <span @class([
                        'relative grid size-10 shrink-0 place-items-center rounded-full',
                        'bg-brand-100 text-brand-600' => ! $notification->read_at,
                        'bg-stone-100 text-stone-400' => $notification->read_at,
                    ])>
                        <x-heroicon-o-bell class="size-5" />
                        @if (! $notification->read_at)
                            <span class="absolute top-0 right-0 size-2.5 rounded-full bg-brand-600 ring-2 ring-white"></span>
                        @endif
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p @class(['text-stone-900', 'font-semibold' => ! $notification->read_at])>{{ $notification->data['title'] ?? 'Thông báo' }}</p>
                            <span class="shrink-0 text-xs whitespace-nowrap text-stone-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-0.5 line-clamp-2 text-sm text-stone-600">{{ $notification->data['body'] ?? '' }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</x-layouts.dashboard>
