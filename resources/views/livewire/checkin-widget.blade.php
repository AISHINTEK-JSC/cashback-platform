<div class="card p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="font-semibold text-stone-900">Điểm danh hằng ngày</p>
            <p class="mt-0.5 flex items-center gap-1 text-sm text-stone-500">
                <x-heroicon-s-fire @class(['size-4', 'text-orange-500' => $streak > 0, 'text-stone-300' => $streak === 0]) />
                Chuỗi hiện tại: <span class="font-semibold text-brand-700">{{ $streak }} ngày</span>
            </p>
        </div>
        <button type="button" wire:click="checkin" @class(['btn shrink-0', 'btn-primary' => ! $checkedIn, 'bg-emerald-50 text-emerald-700' => $checkedIn]) @disabled($checkedIn) wire:loading.attr="disabled">
            @if ($checkedIn)
                <x-heroicon-s-check-circle class="size-5" />
                Đã điểm danh
            @else
                <x-heroicon-o-gift class="size-5" />
                Điểm danh
            @endif
        </button>
    </div>

    <ol class="mt-4 grid grid-cols-7 gap-1.5">
        @foreach ($days as $day)
            @php $isNext = ! $checkedIn && $day['day'] === $streak + 1; @endphp
            <li wire:key="day-{{ $day['day'] }}" @class([
                'flex min-w-0 flex-col items-center rounded-xl border px-0.5 py-2 text-center transition',
                'border-brand-200 bg-brand-50 text-brand-700' => $day['done'],
                'border-brand-500 bg-white text-brand-700 ring-2 ring-brand-500/20' => $isNext,
                'border-stone-200 text-stone-500' => ! $day['done'] && ! $isNext,
            ]) @if ($isNext) aria-current="date" @endif>
                <span class="text-[10px] whitespace-nowrap">{{ $isNext ? 'Hôm nay' : 'Ngày '.$day['day'] }}</span>
                @if ($day['done'])
                    <x-heroicon-s-check-circle class="my-0.5 size-5" />
                @else
                    <x-heroicon-o-gift @class(['my-0.5 size-5', 'animate-bounce' => $isNext]) />
                @endif
                <span class="text-[10px] font-semibold tabular-nums">+{{ number_format($day['reward'], 0, ',', '.') }}</span>
            </li>
        @endforeach
    </ol>

    @if ($message)
        <p class="mt-3 flex items-center gap-2 rounded-xl bg-stone-50 px-3 py-2 text-sm font-medium text-stone-700" role="status">
            <x-heroicon-o-information-circle class="size-5 shrink-0 text-brand-600" />
            {{ $message }}
        </p>
    @endif
</div>
