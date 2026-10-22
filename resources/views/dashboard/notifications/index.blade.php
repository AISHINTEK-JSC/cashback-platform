<x-layouts.dashboard title="Thông báo">
    @if ($notifications->isEmpty())
        <x-empty-state icon="bell" title="Chưa có thông báo" />
    @else
        <form method="POST" action="{{ route('dashboard.notifications.read-all') }}" class="mb-3 text-right">
            @csrf
            <button type="submit" class="text-sm font-semibold text-brand-700 hover:underline">Đánh dấu đã đọc tất cả</button>
        </form>
        <div class="card divide-y divide-stone-100">
            @foreach ($notifications as $notification)
                <a href="{{ route('dashboard.notifications.show', $notification->id) }}" class="flex gap-3 p-4 hover:bg-stone-50">
                    <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-brand-500' => ! $notification->read_at, 'bg-transparent' => $notification->read_at])></span>
                    <div class="min-w-0">
                        <p @class(['text-stone-900', 'font-semibold' => ! $notification->read_at])>{{ $notification->data['title'] ?? 'Thông báo' }}</p>
                        <p class="text-sm text-stone-600">{{ $notification->data['body'] ?? '' }}</p>
                        <p class="mt-1 text-xs text-stone-500">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</x-layouts.dashboard>
