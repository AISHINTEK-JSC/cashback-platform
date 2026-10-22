<x-layouts.dashboard title="Giới thiệu bạn bè">
    <div class="card p-5 sm:p-6">
        <p class="text-stone-600">Chia sẻ link giới thiệu. Bạn nhận hoa hồng trên mỗi đơn của bạn bè (F1) và người do họ mời (F2).</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
            <div class="flex items-center gap-2 rounded-xl bg-stone-100 p-2 pl-3">
                <span class="min-w-0 flex-1 truncate font-mono text-sm">{{ $user->referralUrl() }}</span>
                <x-copy-button :text="$user->referralUrl()" label="Copy link" class="!py-2" />
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-dashed border-brand-300 bg-brand-50 p-2 pl-3">
                <span class="text-sm text-stone-600">Mã:</span>
                <span class="font-mono font-bold tracking-wider text-brand-800">{{ $user->referral_code }}</span>
                <x-copy-button :text="$user->referral_code" label="Copy" class="!py-2" />
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Bạn bè F1" :value="$summary['f1_count']" />
        <x-stat-card label="Bạn bè F2" :value="$summary['f2_count']" />
        <x-stat-card label="Hoa hồng từ F1" :value="vnd($summary['f1_earned'])" />
        <x-stat-card label="Hoa hồng từ F2" :value="vnd($summary['f2_earned'])" />
    </div>

    <h2 class="mt-8 mb-3 font-semibold text-stone-900">Danh sách F1</h2>
    @if ($referrals->isEmpty())
        <x-empty-state icon="users" title="Chưa có ai đăng ký qua link của bạn" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-base min-w-[520px]">
                <thead><tr><th>Thành viên</th><th>Ngày tham gia</th><th class="text-right">F2 của họ</th><th class="text-right">Hoa hồng mang lại</th></tr></thead>
                <tbody>
                    @foreach ($referrals as $referral)
                        <tr>
                            <td class="font-medium text-stone-900">{{ \Illuminate\Support\Str::mask($referral->name, '*', 2, max(1, mb_strlen($referral->name) - 3)) }}</td>
                            <td class="text-stone-600">{{ $referral->created_at->format('d/m/Y') }}</td>
                            <td class="text-right">{{ $referral->referrals_count }}</td>
                            <td class="text-right font-semibold text-emerald-700 tabular-nums">{{ vnd($earnings[$referral->id] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $referrals->links() }}</div>
    @endif
</x-layouts.dashboard>
