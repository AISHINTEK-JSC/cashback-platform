<x-layouts.dashboard title="Giới thiệu bạn bè" description="Nhận hoa hồng trên mỗi đơn của bạn bè (F1) và người do họ mời (F2).">
    <div class="relative overflow-hidden rounded-2xl bg-linear-to-br from-brand-500 to-brand-700 p-5 text-white shadow-lg shadow-brand-600/20 sm:p-6">
        <div class="pointer-events-none absolute -top-20 -right-12 size-60 rounded-full bg-white/10"></div>

        <div class="relative flex items-start gap-3">
            <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-white/15"><x-heroicon-o-gift class="size-6" /></span>
            <div>
                <p class="text-lg font-bold">Mời bạn bè, cùng nhận hoàn tiền</p>
                <p class="text-sm text-brand-100">Chia sẻ link hoặc mã dưới đây. Bạn bè đăng ký và mua hàng, bạn nhận hoa hồng tự động.</p>
            </div>
        </div>

        <div class="relative mt-5 grid gap-3 sm:grid-cols-[1fr_auto]">
            <div class="flex items-center gap-2 rounded-xl bg-white p-2 pl-3 text-stone-900">
                <x-heroicon-o-link class="size-5 shrink-0 text-stone-400" />
                <span class="min-w-0 flex-1 truncate font-mono text-sm">{{ $user->referralUrl() }}</span>
                <x-copy-button :text="$user->referralUrl()" label="Copy link" class="!py-2" />
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-dashed border-white/40 bg-white/10 p-2 pl-3">
                <span class="text-sm text-brand-100">Mã:</span>
                <span class="font-mono font-bold tracking-widest">{{ $user->referral_code }}</span>
                <x-copy-button :text="$user->referral_code" label="Copy" class="!border-0 !bg-white/15 !py-2 !text-white hover:!bg-white/25" />
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat-card label="Bạn bè F1" icon="user" :value="$summary['f1_count']" />
        <x-stat-card label="Bạn bè F2" icon="user-group" :value="$summary['f2_count']" />
        <x-stat-card label="Hoa hồng từ F1" icon="currency-dollar" :value="vnd($summary['f1_earned'])" />
        <x-stat-card label="Hoa hồng từ F2" icon="currency-dollar" :value="vnd($summary['f2_earned'])" />
    </div>

    <x-section-header title="Danh sách F1" class="mt-8" />
    @if ($referrals->isEmpty())
        <x-empty-state icon="users" title="Chưa có ai đăng ký qua link của bạn">Gửi link giới thiệu cho bạn bè qua Zalo, Messenger hoặc Facebook để bắt đầu.</x-empty-state>
    @else
        <div class="card overflow-x-auto">
            <table class="table-base min-w-[520px]">
                <thead><tr><th>Thành viên</th><th>Ngày tham gia</th><th class="text-right">F2 của họ</th><th class="text-right">Hoa hồng mang lại</th></tr></thead>
                <tbody>
                    @foreach ($referrals as $referral)
                        <tr class="hover:bg-stone-50">
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-stone-100 text-xs font-semibold text-stone-600">{{ mb_strtoupper(mb_substr($referral->name, 0, 1)) }}</span>
                                    <span class="font-medium text-stone-900">{{ \Illuminate\Support\Str::mask($referral->name, '*', 2, max(1, mb_strlen($referral->name) - 3)) }}</span>
                                </div>
                            </td>
                            <td class="text-stone-600">{{ $referral->created_at->format('d/m/Y') }}</td>
                            <td class="text-right tabular-nums">{{ $referral->referrals_count }}</td>
                            <td class="text-right font-semibold text-emerald-700 tabular-nums">{{ vnd($earnings[$referral->id] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $referrals->links() }}</div>
    @endif
</x-layouts.dashboard>
