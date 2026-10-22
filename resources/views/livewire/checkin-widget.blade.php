<div class="card p-5">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="font-semibold text-stone-900">Điểm danh hằng ngày</p>
            <p class="text-sm text-stone-500">Chuỗi hiện tại: <span class="font-semibold text-brand-700">{{ $streak }} ngày</span></p>
        </div>
        <button type="button" wire:click="checkin" class="btn btn-primary" @disabled($checkedIn) wire:loading.attr="disabled">
            <x-heroicon-o-gift class="size-5" />
            {{ $checkedIn ? 'Đã điểm danh' : 'Điểm danh' }}
        </button>
    </div>

    <ol class="mt-4 grid grid-cols-7 gap-1.5">
        @foreach ($days as $day)
            <li wire:key="day-{{ $day['day'] }}" @class([
                'flex min-w-0 flex-col items-center rounded-xl border px-0.5 py-2 text-center',
                'border-brand-200 bg-brand-50 text-brand-700' => $day['done'],
                'border-stone-200 text-stone-500' => ! $day['done'],
            ])>
                <span class="text-[10px] whitespace-nowrap">Ngày {{ $day['day'] }}</span>
                @if ($day['done'])
                    <x-heroicon-s-check-circle class="my-0.5 size-5" />
                @else
                    <x-heroicon-o-gift class="my-0.5 size-5" />
                @endif
                <span class="text-[10px] font-semibold tabular-nums">+{{ number_format($day['reward'], 0, ',', '.') }}</span>
            </li>
        @endforeach
    </ol>

    @if ($message)
        <p class="mt-3 text-sm font-medium text-stone-700" role="status">{{ $message }}</p>
    @endif
</div>
