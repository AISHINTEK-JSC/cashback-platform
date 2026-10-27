<x-layouts.dashboard :title="$ticket->subject">
    <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-stone-600">
        <x-status-badge :status="$ticket->status" />
        <span>#{{ $ticket->id }} · {{ $ticket->type->getLabel() }}</span>
        @if ($ticket->platform_order_id)
            <span>· Đơn {{ $ticket->platform?->name }} {{ $ticket->platform_order_id }}</span>
        @endif
    </div>

    @if ($ticket->type === \App\Enums\TicketType::MissingOrder && $ticket->details)
        <dl class="card mb-6 grid gap-x-6 gap-y-3 p-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-xs text-stone-500">Sàn · Mã đơn</dt>
                <dd class="font-medium text-stone-900">{{ $ticket->platform?->name }} · <span class="font-mono">{{ $ticket->platform_order_id }}</span></dd>
            </div>
            <div>
                <dt class="text-xs text-stone-500">Ngày đặt hàng</dt>
                <dd class="font-medium text-stone-900">{{ \Illuminate\Support\Carbon::parse($ticket->details['purchased_at'])->format('d/m/Y') }}</dd>
            </div>
            <div>
                <dt class="text-xs text-stone-500">Giá trị đơn</dt>
                <dd class="font-medium text-stone-900">{{ filled($ticket->details['order_amount'] ?? null) ? vnd($ticket->details['order_amount']) : 'Không khai báo' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-stone-500">Link hoàn tiền đã dùng</dt>
                <dd class="truncate font-medium text-stone-900">{{ $claimLink ? $claimLink->created_at->format('d/m H:i').' · '.($claimLink->product_name ?? 'Sản phẩm') : 'Không chọn' }}</dd>
            </div>
        </dl>
    @endif

    <div class="space-y-4">
        @foreach ($ticket->messages as $message)
            @php $mine = $message->user_id === auth()->id(); @endphp
            <div @class(['flex', 'justify-end' => $mine])>
                <div @class(['max-w-xl rounded-2xl px-4 py-3', 'bg-brand-600 text-white' => $mine, 'card' => ! $mine])>
                    <p @class(['text-xs font-semibold', 'text-white/80' => $mine, 'text-brand-700' => ! $mine])>{{ $mine ? 'Bạn' : 'Hỗ trợ viên' }} · {{ $message->created_at?->format('d/m H:i') }}</p>
                    <p class="mt-1 text-sm whitespace-pre-line">{{ $message->message }}</p>
                    @foreach ($message->attachments ?? [] as $path)
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('uploads')->url($path) }}" target="_blank" class="mt-2 block"><img src="{{ \Illuminate\Support\Facades\Storage::disk('uploads')->url($path) }}" alt="" class="max-h-48 rounded-lg"></a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @if ($ticket->status !== \App\Enums\TicketStatus::Closed)
        <form method="POST" action="{{ route('dashboard.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="card mt-6 space-y-3 p-4">
            @csrf
            <textarea name="message" rows="3" class="input" placeholder="Nhập tin nhắn..." required>{{ old('message') }}</textarea>
            @error('message') <p class="field-error">{{ $message }}</p> @enderror
            <div class="flex flex-wrap items-center justify-between gap-3">
                <input type="file" name="attachments[]" accept="image/*" multiple class="text-sm text-stone-600 file:mr-3 file:rounded-lg file:border-0 file:bg-stone-100 file:px-3 file:py-2 file:font-medium">
                <button type="submit" class="btn btn-primary">Gửi</button>
            </div>
        </form>
    @else
        <p class="mt-6 text-center text-sm text-stone-500">Yêu cầu đã đóng. <a href="{{ route('dashboard.tickets.create') }}" class="font-semibold text-brand-700 underline">Tạo yêu cầu mới</a></p>
    @endif
</x-layouts.dashboard>
