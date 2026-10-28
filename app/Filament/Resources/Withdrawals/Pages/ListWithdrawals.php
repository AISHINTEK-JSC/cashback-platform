<?php

namespace App\Filament\Resources\Withdrawals\Pages;

use App\Enums\WithdrawalMethod;
use App\Enums\WithdrawalStatus;
use App\Filament\Resources\Withdrawals\WithdrawalResource;
use App\Models\Withdrawal;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListWithdrawals extends ListRecords
{
    protected static string $resource = WithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Xuất lệnh chuyển khoản (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $out = fopen('php://output', 'w');
                    fwrite($out, "\xEF\xBB\xBF");
                    fputcsv($out, ['Mã', 'Ngân hàng', 'Số tài khoản', 'Chủ tài khoản', 'Số tiền chuyển', 'Nội dung']);

                    Withdrawal::query()
                        ->where('method', WithdrawalMethod::Bank)
                        ->whereIn('status', [WithdrawalStatus::Approved, WithdrawalStatus::Processing])
                        ->orderBy('id')
                        ->each(fn (Withdrawal $w) => fputcsv($out, [$w->code, $w->bank_name, $w->account_number, $w->account_name, $w->net_amount, "Hoan tien {$w->code}"]));

                    fclose($out);
                }, 'lenh-chuyen-khoan-'.now()->format('Ymd-His').'.csv')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'open' => Tab::make('Cần xử lý')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing])),
            'paid' => Tab::make('Đã thanh toán')->modifyQueryUsing(fn (Builder $query) => $query->where('status', WithdrawalStatus::Paid)),
            'closed' => Tab::make('Từ chối / huỷ')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [WithdrawalStatus::Rejected, WithdrawalStatus::Cancelled])),
            'all' => Tab::make('Tất cả'),
        ];
    }
}
