<x-layouts.dashboard title="Hỗ trợ / khiếu nại" description="Gặp vấn đề với đơn hàng hoặc rút tiền? Gửi yêu cầu, đội hỗ trợ sẽ phản hồi tại đây.">
    <x-slot:actions>
        <a href="{{ route('dashboard.tickets.create') }}" class="btn btn-primary"><x-heroicon-o-paper-airplane class="size-5" /> Gửi yêu cầu mới</a>
    </x-slot:actions>

    @if ($tickets->isEmpty())
        <x-empty-state icon="lifebuoy" title="Chưa có yêu cầu hỗ trợ" action="Gửi yêu cầu mới" :action-url="route('dashboard.tickets.create')">
            Đơn hàng không ghi nhận, tiền hoàn sai hay rút tiền chậm? Gửi yêu cầu để được hỗ trợ.
        </x-empty-state>
    @else
        <div class="card divide-y divide-stone-100 overflow-hidden">
            @foreach ($tickets as $ticket)
                <a href="{{ route('dashboard.tickets.show', $ticket) }}" class="group flex items-center gap-4 p-4 transition hover:bg-stone-50">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-stone-100 text-stone-500">
                        <x-heroicon-o-chat-bubble-left-right class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium text-stone-900">{{ $ticket->subject }}</p>
                        <p class="text-xs text-stone-500">#{{ $ticket->id }} · {{ $ticket->type->getLabel() }} · cập nhật {{ $ticket->updated_at->diffForHumans() }}</p>
                    </div>
                    <x-status-badge :status="$ticket->status" />
                    <x-heroicon-m-chevron-right class="hidden size-5 text-stone-300 transition group-hover:text-stone-500 sm:block" />
                </a>
            @endforeach
        </div>
        <div class="mt-4">{{ $tickets->links() }}</div>
    @endif
</x-layouts.dashboard>
