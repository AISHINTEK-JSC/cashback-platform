<x-layouts.dashboard title="Điểm danh" description="Điểm danh mỗi ngày để nhận thưởng, chuỗi càng dài thưởng càng lớn.">
    <livewire:checkin-widget />

    <x-section-header title="Lịch sử điểm danh" class="mt-8" />
    @if ($history->isEmpty())
        <x-empty-state icon="calendar-days" title="Bạn chưa điểm danh lần nào">Bấm "Điểm danh" ở phía trên để nhận thưởng ngày đầu tiên.</x-empty-state>
    @else
        <div class="card overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Ngày</th><th>Chuỗi</th><th class="text-right">Thưởng</th></tr></thead>
                <tbody>
                    @foreach ($history as $checkin)
                        <tr class="hover:bg-stone-50">
                            <td class="whitespace-nowrap text-stone-900">{{ $checkin->checkin_date->format('d/m/Y') }}</td>
                            <td>
                                <span class="inline-flex items-center gap-1 text-stone-600">
                                    <x-heroicon-s-fire class="size-4 text-orange-500" />
                                    Ngày thứ {{ $checkin->streak }}
                                </span>
                            </td>
                            <td class="text-right font-semibold text-emerald-700 tabular-nums">+{{ vnd($checkin->reward_amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $history->links() }}</div>
    @endif
</x-layouts.dashboard>
