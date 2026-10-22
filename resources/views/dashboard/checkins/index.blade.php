<x-layouts.dashboard title="Điểm danh">
    <livewire:checkin-widget />

    <h2 class="mt-8 mb-3 font-semibold text-stone-900">Lịch sử điểm danh</h2>
    @if ($history->isEmpty())
        <x-empty-state icon="calendar-days" title="Bạn chưa điểm danh lần nào" />
    @else
        <div class="card overflow-x-auto">
            <table class="table-base">
                <thead><tr><th>Ngày</th><th>Chuỗi</th><th class="text-right">Thưởng</th></tr></thead>
                <tbody>
                    @foreach ($history as $checkin)
                        <tr>
                            <td>{{ $checkin->checkin_date->format('d/m/Y') }}</td>
                            <td>Ngày thứ {{ $checkin->streak }}</td>
                            <td class="text-right font-semibold text-emerald-700 tabular-nums">+{{ vnd($checkin->reward_amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $history->links() }}</div>
    @endif
</x-layouts.dashboard>
