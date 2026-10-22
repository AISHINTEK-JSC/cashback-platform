@php
    $percent = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',').'%';
@endphp

<x-layouts.app title="Tỷ lệ hoàn tiền" description="Bảng tỷ lệ chia hoa hồng hoàn tiền theo sàn và ngành hàng, hoa hồng giới thiệu F1, F2 và hạng thành viên.">
    <div class="container-page py-10">
        <h1 class="text-3xl font-bold text-stone-900">Tỷ lệ hoàn tiền</h1>
        <p class="mt-2 max-w-3xl text-stone-600">
            Sàn trả hoa hồng tiếp thị liên kết theo từng sản phẩm (thường 2-15% giá trị đơn). Chúng tôi chia lại cho bạn theo tỷ lệ dưới đây.
            Ví dụ: đơn 500.000đ có hoa hồng 8% = 40.000đ, với tỷ lệ 70% bạn nhận <strong>28.000đ</strong>.
        </p>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            @forelse ($platforms as $platform)
                <div class="card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-stone-200 px-5 py-4">
                        <x-platform-badge :platform="$platform" class="!text-sm" />
                        <span class="text-xs text-stone-500">Chốt đơn sau ~{{ $platform->lock_days }} ngày</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table-base">
                            <thead>
                                <tr><th>Ngành hàng</th><th class="text-right">Bạn nhận</th><th class="text-right">F1</th><th class="text-right">F2</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($rules[$platform->id] ?? [] as $rule)
                                    <tr>
                                        <td>{{ $rule->category_code ?? 'Mặc định' }}</td>
                                        <td class="text-right font-semibold text-emerald-700">{{ $percent($rule->user_rate) }}</td>
                                        <td class="text-right">{{ $percent($rule->f1_rate) }}</td>
                                        <td class="text-right">{{ $percent($rule->f2_rate) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-stone-500">Đang cập nhật.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <x-empty-state icon="receipt-percent" title="Chưa có sàn nào được bật" />
            @endforelse
        </div>

        @if ($levels->isNotEmpty())
            <h2 class="mt-12 text-2xl font-bold text-stone-900">Hạng thành viên</h2>
            <p class="mt-2 text-stone-600">Hạng càng cao, phần hoàn tiền của bạn càng được cộng thêm.</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($levels as $level)
                    <div class="card p-5">
                        <p class="font-bold text-stone-900">{{ $level->name }}</p>
                        <p class="mt-1 text-sm text-stone-600">Từ {{ $level->min_approved_orders }} đơn được duyệt</p>
                        <p class="mt-3 text-2xl font-bold text-brand-600">+{{ $percent($level->bonus_rate) }}</p>
                        <p class="text-xs text-stone-500">cộng thêm trên phần hoàn của bạn</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
