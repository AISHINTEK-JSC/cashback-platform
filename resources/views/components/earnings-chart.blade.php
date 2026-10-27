@props(['earnings'])

@php
    $seriesKeys = array_keys($earnings['series']);
    $months = $earnings['months'];
    // Trục y tròn số đẹp (1, 1.5, 2, 2.5, 3, 4, 5, 6, 8 x 10^n) để vạch lưới dễ đọc mà cột vẫn cao
    $raw = max(1, $earnings['max']);
    $magnitude = 10 ** floor(log10($raw));
    $niceMax = (int) collect([1, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10])->map(fn ($step) => $step * $magnitude)->first(fn ($value) => $value >= $raw);
    $short = fn (int $value): string => match (true) {
        $value >= 1_000_000 => rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',').'tr',
        $value >= 1000 => number_format($value / 1000, 0, ',', '.').'k',
        default => (string) $value,
    };
@endphp

{{-- Cột chồng theo tháng: cột <= 24px, bo 4px ở đầu cột, khe 2px giữa các phần, tooltip khi rê chuột/focus, có bảng số liệu --}}
<div
    {{ $attributes->class(['viz-earnings card p-5 sm:p-6']) }}
    x-data="{ active: null, months: @js($months), latest: {{ count($months) - 1 }}, money: (value) => new Intl.NumberFormat('vi-VN').format(value) + 'đ' }"
>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-semibold text-stone-900">Thu nhập theo tháng</h2>
            <p class="text-sm text-stone-500">Tiền đã vào số dư có thể rút trong 12 tháng gần nhất, chưa trừ các khoản thu hồi.</p>
        </div>
        <div class="sm:text-right">
            <p class="text-xs text-stone-500">Tổng 12 tháng</p>
            <p class="text-lg font-bold text-stone-900 tabular-nums">{{ vnd($earnings['total']) }}</p>
        </div>
    </div>

    @if ($earnings['total'] === 0)
        <p class="mt-6 rounded-xl bg-stone-50 p-6 text-center text-sm text-stone-500">Chưa có thu nhập trong 12 tháng qua. Tiền hoàn của đơn hàng sẽ hiện ở đây khi sàn duyệt đơn.</p>
    @else
        {{-- Chú giải: luôn có với >= 2 nhóm, kèm số tiền của tháng đang chọn (mặc định tháng gần nhất) --}}
        <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-1.5 text-sm" aria-label="Chú giải">
            @foreach ($earnings['series'] as $key => $label)
                <li class="flex items-center gap-2">
                    <span class="size-2.5 rounded-sm" style="background: var(--series-{{ $loop->iteration }})"></span>
                    <span class="text-stone-600">{{ $label }}</span>
                    <span class="font-semibold text-stone-900 tabular-nums" x-text="money(months[active ?? latest].values['{{ $key }}'])">{{ vnd(end($months)['values'][$key]) }}</span>
                </li>
            @endforeach
            <li class="text-stone-500" x-text="'Tháng ' + months[active ?? latest].label">Tháng {{ end($months)['label'] }}</li>
        </ul>

        <div class="relative mt-7 h-48 pl-10">
            {{-- Lưới và nhãn trục y, nét nhạt để không lấn dữ liệu; hàng cao 0 để vạch nằm đúng mức giá trị --}}
            @foreach ([$niceMax, intdiv($niceMax, 2), 0] as $tick)
                <div class="pointer-events-none absolute inset-x-0 flex h-0 items-center" style="bottom: {{ $niceMax > 0 ? $tick / $niceMax * 100 : 0 }}%">
                    <span class="w-9 shrink-0 pr-1 text-right text-[11px] text-stone-400 tabular-nums">{{ $short($tick) }}</span>
                    <span @class(['h-px flex-1', 'bg-stone-300' => $tick === 0, 'bg-stone-100' => $tick !== 0])></span>
                </div>
            @endforeach

            <div class="relative flex h-full items-end gap-1 sm:gap-2">
                @foreach ($months as $month)
                    @php
                        $topKey = collect($seriesKeys)->reverse()->first(fn ($key) => $month['values'][$key] > 0);
                    @endphp
                    <div
                        class="relative flex h-full flex-1 flex-col items-center justify-end outline-none focus-visible:rounded-md focus-visible:ring-2 focus-visible:ring-brand-500/40"
                        tabindex="0"
                        @mouseenter="active = {{ $loop->index }}"
                        @mouseleave="active = null"
                        @focus="active = {{ $loop->index }}"
                        @blur="active = null"
                        aria-label="Tháng {{ $month['label'] }}: {{ vnd($month['total']) }}"
                    >
                        @if ($month['total'] > 0)
                            <div
                                class="flex w-full max-w-6 flex-col-reverse gap-[2px] transition-opacity"
                                :class="active !== null && active !== {{ $loop->index }} ? 'opacity-40' : ''"
                                style="height: {{ max(2, $month['total'] / $niceMax * 100) }}%"
                            >
                                @foreach ($seriesKeys as $key)
                                    @if ($month['values'][$key] > 0)
                                        <div @class(['min-h-[2px]', 'rounded-t-[4px]' => $key === $topKey]) style="flex: {{ $month['values'][$key] }} 1 0%; background: var(--series-{{ $loop->iteration }})"></div>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        {{-- Tooltip: giá trị in đậm đứng trước, nhãn theo sau; chữ dùng màu chữ, không dùng màu nhóm --}}
                        <div
                            x-show="active === {{ $loop->index }}"
                            x-cloak
                            @class([
                                'pointer-events-none absolute bottom-full z-10 mb-2 w-48 rounded-xl border border-stone-200 bg-surface p-3 text-left shadow-lg',
                                'left-0' => $loop->index < 2,
                                'right-0' => $loop->index > count($months) - 3,
                                'left-1/2 -translate-x-1/2' => $loop->index >= 2 && $loop->index <= count($months) - 3,
                            ])
                        >
                            <p class="text-xs text-stone-500">Tháng {{ $month['label'] }}</p>
                            <p class="text-sm font-bold text-stone-900 tabular-nums">{{ vnd($month['total']) }}</p>
                            <ul class="mt-1.5 space-y-1 text-xs">
                                @foreach ($earnings['series'] as $key => $label)
                                    <li class="flex items-center gap-2">
                                        <span class="h-0.5 w-3 rounded-full" style="background: var(--series-{{ $loop->iteration }})"></span>
                                        <span class="font-semibold text-stone-900 tabular-nums">{{ vnd($month['values'][$key]) }}</span>
                                        <span class="truncate text-stone-500">{{ $label }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Nhãn tháng căn giữa đúng cột; điện thoại hiện cách một tháng, luôn giữ tháng gần nhất --}}
        <div class="mt-2 flex gap-1 pl-10 sm:gap-2">
            @foreach ($months as $month)
                <span class="flex min-w-0 flex-1 justify-center">
                    <span @class(['text-[11px] whitespace-nowrap text-stone-500 tabular-nums', 'invisible sm:visible' => (count($months) - 1 - $loop->index) % 2 === 1])>{{ substr($month['label'], 0, 2) }}/{{ substr($month['label'], -2) }}</span>
                </span>
            @endforeach
        </div>

        <details class="mt-4">
            <summary class="cursor-pointer text-sm font-medium text-brand-700">Xem dạng bảng</summary>
            <div class="mt-3 overflow-x-auto">
                <table class="table-base min-w-[480px]">
                    <thead>
                        <tr>
                            <th>Tháng</th>
                            @foreach ($earnings['series'] as $label)
                                <th class="text-right">{{ $label }}</th>
                            @endforeach
                            <th class="text-right">Tổng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (array_reverse($months) as $month)
                            <tr>
                                <td class="text-stone-600">{{ $month['label'] }}</td>
                                @foreach ($seriesKeys as $key)
                                    <td class="text-right tabular-nums">{{ vnd($month['values'][$key]) }}</td>
                                @endforeach
                                <td class="text-right font-semibold tabular-nums">{{ vnd($month['total']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif
</div>
