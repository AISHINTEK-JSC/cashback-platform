@php
    $siteName = setting('site_name');
    $duration = function (?int $minutes): string {
        return match (true) {
            $minutes === null => '-',
            $minutes < 60 => max(1, $minutes).' phút',
            $minutes < 60 * 48 => round($minutes / 60).' giờ',
            default => round($minutes / 1440).' ngày',
        };
    };
    $monthly = $stats['monthly'];
    $monthlyMax = max(1, collect($monthly)->max('amount'));
@endphp

<x-layouts.app title="Minh bạch chi trả" :description="'Số liệu chi trả công khai của '.$siteName.': tổng tiền đã chuyển cho thành viên, thời gian xử lý rút tiền và các lần chi trả gần đây.'">
    <section class="border-b border-stone-200 bg-gradient-to-b from-brand-50 to-stone-50">
        <div class="container-page py-12 text-center sm:py-16">
            <p class="inline-flex items-center gap-2 rounded-full bg-surface px-3 py-1 text-sm font-medium text-brand-700 ring-1 ring-brand-200">
                <x-heroicon-o-shield-check class="size-4" /> Số liệu thật, cập nhật mỗi giờ
            </p>
            <h1 class="mx-auto mt-5 max-w-3xl text-3xl font-bold tracking-tight text-balance text-stone-900 sm:text-4xl">Minh bạch chi trả</h1>
            <p class="mx-auto mt-3 max-w-2xl text-stone-600">Mọi khoản tiền hoàn đều được ghi sổ và chuyển khoản thật về tài khoản ngân hàng của thành viên. Dưới đây là số liệu tổng hợp từ hệ thống, không chứa thông tin cá nhân.</p>
            <p class="mt-3 text-xs text-stone-500">Cập nhật lúc {{ \Illuminate\Support\Carbon::parse($stats['updated_at'])->format('H:i d/m/Y') }}</p>
        </div>
    </section>

    <section class="container-page py-10">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Đã chuyển khoản cho thành viên" icon="banknotes" :value="vnd($stats['total_paid'])" tone="brand" :hint="number_format($stats['paid_count'], 0, ',', '.').' lần chi trả'" />
            <x-stat-card label="Thời gian xử lý rút tiền" icon="clock" :value="$duration($stats['median_minutes'])" hint="Trung vị từ lúc tạo yêu cầu đến lúc chuyển khoản" />
            <x-stat-card label="Thành viên đã nhận tiền" icon="users" :value="number_format($stats['paid_members'], 0, ',', '.')" :hint="'Trên '.number_format($stats['members'], 0, ',', '.').' thành viên'" />
            <x-stat-card label="Tiền hoàn đã cộng vào ví" icon="wallet" :value="vnd($stats['total_credited'])" :hint="number_format($stats['orders'], 0, ',', '.').' đơn hàng được ghi nhận'" />
        </div>
    </section>

    @if ($stats['paid_count'] === 0)
        <section class="container-page pb-6">
            <x-empty-state icon="banknotes" title="Chưa có lần chi trả nào">{{ $siteName }} mới ra mắt. Số liệu chi trả sẽ tự cập nhật ở đây khi có yêu cầu rút tiền đầu tiên được chuyển khoản.</x-empty-state>
        </section>
    @else
        <section class="container-page grid gap-6 pb-6 lg:grid-cols-5">
            {{-- Một nhóm số liệu nên không cần chú giải, tiêu đề đã nói rõ; có tooltip và bảng số liệu --}}
            <div class="card p-5 sm:p-6 lg:col-span-3" x-data="{ active: null }">
                <h2 class="font-semibold text-stone-900">Tiền đã chi trả 6 tháng gần nhất</h2>
                <p class="text-sm text-stone-500">Tổng tiền chuyển khoản cho thành viên theo tháng.</p>

                <div class="relative mt-8 h-44">
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-stone-300"></div>
                    <div class="relative flex h-full items-end gap-3 sm:gap-6">
                        @foreach ($monthly as $month)
                            <div
                                class="relative flex h-full flex-1 flex-col items-center justify-end outline-none focus-visible:rounded-md focus-visible:ring-2 focus-visible:ring-brand-500/40"
                                tabindex="0"
                                @mouseenter="active = {{ $loop->index }}"
                                @mouseleave="active = null"
                                @focus="active = {{ $loop->index }}"
                                @blur="active = null"
                                aria-label="Tháng {{ $month['label'] }}: {{ vnd($month['amount']) }}"
                            >
                                @if ($month['amount'] > 0)
                                    <div
                                        class="w-full max-w-6 rounded-t-[4px] bg-brand-500 transition-opacity"
                                        :class="active !== null && active !== {{ $loop->index }} ? 'opacity-40' : ''"
                                        style="height: {{ max(2, $month['amount'] / $monthlyMax * 100) }}%"
                                    ></div>
                                @endif
                                <div
                                    x-show="active === {{ $loop->index }}"
                                    x-cloak
                                    @class([
                                        'pointer-events-none absolute bottom-full z-10 mb-2 w-40 rounded-xl border border-stone-200 bg-surface p-3 text-left shadow-lg',
                                        'left-0' => $loop->first,
                                        'right-0' => $loop->last,
                                        'left-1/2 -translate-x-1/2' => ! $loop->first && ! $loop->last,
                                    ])
                                >
                                    <p class="text-sm font-bold text-stone-900 tabular-nums">{{ vnd($month['amount']) }}</p>
                                    <p class="text-xs text-stone-500">Tháng {{ $month['label'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-2 flex gap-3 sm:gap-6">
                    @foreach ($monthly as $month)
                        <span class="flex min-w-0 flex-1 justify-center text-[11px] whitespace-nowrap text-stone-500 tabular-nums">{{ $month['label'] }}</span>
                    @endforeach
                </div>

                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-medium text-brand-700">Xem dạng bảng</summary>
                    <table class="table-base mt-3">
                        <thead><tr><th>Tháng</th><th class="text-right">Đã chi trả</th></tr></thead>
                        <tbody>
                            @foreach (array_reverse($monthly) as $month)
                                <tr><td class="text-stone-600">{{ $month['label'] }}</td><td class="text-right font-semibold tabular-nums">{{ vnd($month['amount']) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </details>
            </div>

            <div class="card p-5 sm:p-6 lg:col-span-2">
                <h2 class="font-semibold text-stone-900">Chi trả gần đây</h2>
                <p class="text-sm text-stone-500">Tên đã được che để bảo vệ thành viên.</p>
                <ul class="mt-4 divide-y divide-stone-100">
                    @foreach ($stats['recent'] as $payout)
                        <li class="flex items-center gap-3 py-2.5">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-emerald-50 text-emerald-600"><x-heroicon-m-check class="size-4" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-stone-900">{{ $payout['member'] }} · {{ $payout['bank'] }}</p>
                                <p class="text-xs text-stone-500">
                                    {{ $payout['paid_at'] ? \Illuminate\Support\Carbon::parse($payout['paid_at'])->diffForHumans() : '' }}
                                    @if ($payout['minutes'] !== null)
                                        · xử lý trong {{ $duration($payout['minutes']) }}
                                    @endif
                                </p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold text-emerald-700 tabular-nums">{{ vnd($payout['amount']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section class="container-page max-w-3xl py-10">
        <h2 class="text-2xl font-bold text-stone-900">Cam kết của chúng tôi</h2>
        <ul class="mt-6 space-y-4">
            @foreach ([
                ['icon' => 'document-check', 'title' => 'Mọi đồng tiền đều được ghi sổ', 'body' => 'Mỗi khoản cộng, trừ trong ví đều có dòng lịch sử riêng, không thể sửa xoá. Bạn xem và tải toàn bộ lịch sử trong mục Biến động số dư.'],
                ['icon' => 'banknotes', 'title' => 'Rút tiền về ngân hàng trong 1–3 ngày làm việc', 'body' => 'Rút tối thiểu '.vnd((int) setting('withdraw_min')).', '.((int) setting('withdraw_fee') > 0 ? 'phí '.vnd((int) setting('withdraw_fee')).' mỗi lần' : 'không mất phí').'. Nội dung chuyển khoản là mã yêu cầu để bạn dễ đối chiếu.'],
                ['icon' => 'scale', 'title' => 'Tiền hoàn theo hoa hồng thật của sàn', 'body' => 'Chúng tôi chia lại phần lớn hoa hồng sàn trả theo tỷ lệ công khai. Đơn bị huỷ hoặc trả hàng thì không được hoàn.'],
            ] as $item)
                <li class="flex gap-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><x-dynamic-component :component="'heroicon-o-'.$item['icon']" class="size-6" /></span>
                    <div>
                        <p class="font-semibold text-stone-900">{{ $item['title'] }}</p>
                        <p class="mt-1 text-sm text-stone-600">{{ $item['body'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="mt-8 text-sm text-stone-600">
            Xem thêm <a href="{{ route('rates') }}" class="font-semibold text-brand-700 hover:underline">tỷ lệ hoàn tiền</a>,
            <a href="{{ route('pages.terms') }}" class="font-semibold text-brand-700 hover:underline">điều khoản sử dụng</a>
            hoặc <a href="{{ route('pages.faq') }}" class="font-semibold text-brand-700 hover:underline">câu hỏi thường gặp</a>.
        </p>
    </section>
</x-layouts.app>
