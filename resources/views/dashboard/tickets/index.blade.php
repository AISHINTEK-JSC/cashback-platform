<x-layouts.dashboard title="Hỗ trợ / khiếu nại">
    <div class="mb-4 flex justify-end">
        <a href="{{ route('dashboard.tickets.create') }}" class="btn btn-primary"><x-heroicon-o-paper-airplane class="size-5" /> Gửi yêu cầu mới</a>
    </div>

    @if ($tickets->isEmpty())
        <x-empty-state icon="lifebuoy" title="Chưa có yêu cầu hỗ trợ">Gặp vấn đề với đơn hàng hoặc rút tiền? Gửi yêu cầu để được hỗ trợ.</x-empty-state>
    @else
        <div class="card divide-y divide-stone-100">
            @foreach ($tickets as $ticket)
                <a href="{{ route('dashboard.tickets.show', $ticket) }}" class="flex items-center gap-4 p-4 hover:bg-stone-50">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-stone-900">{{ $ticket->subject }}</p>
                        <p class="text-xs text-stone-500">#{{ $ticket->id }} · {{ $ticket->type->getLabel() }} · cập nhật {{ $ticket->updated_at->diffForHumans() }}</p>
                    </div>
                    <x-status-badge :status="$ticket->status" />
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $tickets->links() }}</div>
    @endif
</x-layouts.dashboard>
