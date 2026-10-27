<?php

namespace App\Services;

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalStatusChanged;
use App\Services\Fraud\FraudDetector;
use Illuminate\Support\Facades\DB;

/**
 * Yêu cầu rút tiền: khả dụng → đóng băng khi tạo; đóng băng → đã chi khi thanh toán, hoặc trả về khả dụng khi từ chối/huỷ.
 */
class WithdrawalService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly FraudDetector $fraud,
    ) {}

    public function fee(int $amount): int
    {
        return min($amount, (int) setting('withdraw_fee'));
    }

    public function request(User $user, UserBankAccount $bankAccount, int $amount, ?string $ip = null): Withdrawal
    {
        if ($bankAccount->user_id !== $user->id) {
            throw new BusinessRuleException('Tài khoản ngân hàng không hợp lệ.');
        }

        if ($availableAt = $bankAccount->withdrawableFrom()) {
            throw new BusinessRuleException("Tài khoản {$bankAccount->bank_name} mới được thêm. Để bảo vệ bạn, tài khoản này nhận được tiền rút từ {$availableAt->format('H:i d/m/Y')}.");
        }

        $min = (int) setting('withdraw_min');
        $max = (int) setting('withdraw_max');

        if ($amount < $min) {
            throw new BusinessRuleException('Số tiền rút tối thiểu là '.vnd($min).'.');
        }

        if ($max > 0 && $amount > $max) {
            throw new BusinessRuleException('Số tiền rút tối đa mỗi lần là '.vnd($max).'.');
        }

        if ($this->fraud->hasConfirmedFlag($user)) {
            throw new BusinessRuleException('Tài khoản đang bị tạm khoá rút tiền. Vui lòng liên hệ hỗ trợ.');
        }

        return DB::transaction(function () use ($user, $bankAccount, $amount, $ip): Withdrawal {
            // Khoá dòng ví để các yêu cầu rút đồng thời của cùng user xếp hàng tuần tự;
            // khoá trên withdrawals không đủ vì khi chưa có yêu cầu nào thì không có dòng để khoá.
            Wallet::query()->whereKey($user->id)->lockForUpdate()->first();

            $openRequests = $user->withdrawals()
                ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing])
                ->count();

            if ($openRequests >= (int) setting('withdraw_max_pending')) {
                throw new BusinessRuleException('Bạn đang có yêu cầu rút tiền chưa xử lý xong.');
            }

            $fee = $this->fee($amount);

            $withdrawal = $user->withdrawals()->create([
                'code' => 'RT'.now()->format('ymdHis').random_int(10, 99),
                'bank_account_id' => $bankAccount->id,
                'bank_name' => $bankAccount->bank_name,
                'account_number' => $bankAccount->account_number,
                'account_name' => $bankAccount->account_name,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $amount - $fee,
                'status' => WithdrawalStatus::Pending,
                'request_ip' => $ip,
            ]);

            $withdrawal->update(['code' => sprintf('RT%06d', $withdrawal->id)]);

            $description = "Yêu cầu rút tiền {$withdrawal->code}";
            $this->wallet->post($user, WalletTransactionType::Withdraw, WalletBucket::Available, -$amount, $withdrawal, $description);
            $this->wallet->post($user, WalletTransactionType::Withdraw, WalletBucket::Frozen, $amount, $withdrawal, $description);

            return $withdrawal;
        });
    }

    public function approve(Withdrawal $withdrawal, User $admin): void
    {
        $this->move($withdrawal, [WithdrawalStatus::Pending], WithdrawalStatus::Approved, $admin);
    }

    public function markProcessing(Withdrawal $withdrawal, User $admin): void
    {
        $this->move($withdrawal, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], WithdrawalStatus::Processing, $admin);
    }

    public function markPaid(Withdrawal $withdrawal, User $admin, ?string $bankTxnRef = null): void
    {
        DB::transaction(function () use ($withdrawal, $admin, $bankTxnRef): void {
            $this->move($withdrawal, [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing], WithdrawalStatus::Paid, $admin, [
                'bank_txn_ref' => $bankTxnRef,
                'paid_at' => now(),
            ]);

            $this->wallet->post($withdrawal->user_id, WalletTransactionType::Payout, WalletBucket::Frozen, -$withdrawal->amount, $withdrawal, "Đã chuyển khoản {$withdrawal->code}", $admin->id);
        });
    }

    public function reject(Withdrawal $withdrawal, User $admin, string $reason): void
    {
        DB::transaction(function () use ($withdrawal, $admin, $reason): void {
            $this->move($withdrawal, [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing], WithdrawalStatus::Rejected, $admin, ['reject_reason' => $reason]);
            $this->refund($withdrawal, "Hoàn tiền yêu cầu {$withdrawal->code} bị từ chối", $admin->id);
        });
    }

    public function cancelByUser(Withdrawal $withdrawal, User $user): void
    {
        if ($withdrawal->user_id !== $user->id) {
            throw new BusinessRuleException('Không tìm thấy yêu cầu rút tiền.');
        }

        DB::transaction(function () use ($withdrawal): void {
            $locked = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WithdrawalStatus::Pending) {
                throw new BusinessRuleException('Chỉ huỷ được yêu cầu đang chờ duyệt.');
            }

            $locked->update(['status' => WithdrawalStatus::Cancelled]);
            $this->refund($locked, "Huỷ yêu cầu rút tiền {$locked->code}");
            $withdrawal->refresh();
        });
    }

    private function refund(Withdrawal $withdrawal, string $description, ?int $createdBy = null): void
    {
        $this->wallet->post($withdrawal->user_id, WalletTransactionType::WithdrawRefund, WalletBucket::Frozen, -$withdrawal->amount, $withdrawal, $description, $createdBy);
        $this->wallet->post($withdrawal->user_id, WalletTransactionType::WithdrawRefund, WalletBucket::Available, $withdrawal->amount, $withdrawal, $description, $createdBy);
    }

    /**
     * @param  list<WithdrawalStatus>  $from
     * @param  array<string, mixed>  $attributes
     */
    private function move(Withdrawal $withdrawal, array $from, WithdrawalStatus $to, User $admin, array $attributes = []): void
    {
        DB::transaction(function () use ($withdrawal, $from, $to, $admin, $attributes): void {
            $locked = Withdrawal::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, $from, true)) {
                throw new BusinessRuleException("Không thể chuyển yêu cầu {$locked->code} từ \"{$locked->status->getLabel()}\" sang \"{$to->getLabel()}\".");
            }

            $locked->update([...$attributes, 'status' => $to, 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
            $withdrawal->setRawAttributes($locked->getAttributes(), true);
        });

        $withdrawal->user->notify(new WithdrawalStatusChanged($withdrawal));
    }
}
