<?php

namespace App\Services;

use App\Enums\PhoneCarrier;
use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalMethod;
use App\Enums\WithdrawalStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Notifications\WithdrawalStatusChanged;
use App\Services\Fraud\FraudDetector;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

/**
 * Yêu cầu rút tiền: khả dụng → đóng băng khi tạo; đóng băng → đã chi khi thanh toán, hoặc trả về khả dụng khi từ chối/huỷ.
 * Nhận tiền qua chuyển khoản (ngân hàng/ví), nạp tiền điện thoại hoặc thẻ cào; hai cách sau admin xử lý tay theo mệnh giá cố định.
 */
class WithdrawalService
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly FraudDetector $fraud,
        private readonly Settings $settings,
    ) {}

    public function fee(int $amount): int
    {
        return min($amount, (int) setting('withdraw_fee'));
    }

    /**
     * Mệnh giá nhỏ nhất rút được bằng nạp điện thoại / thẻ cào, null khi admin tắt cả hai cách.
     */
    public function phoneMinimum(): ?int
    {
        if (! WithdrawalMethod::PhoneTopup->isEnabled() && ! WithdrawalMethod::PhoneCard->isEnabled()) {
            return null;
        }

        return $this->settings->phoneWithdrawAmounts()[0] ?? null;
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

        return $this->open($user, $amount, $this->fee($amount), [
            'method' => WithdrawalMethod::Bank,
            'bank_account_id' => $bankAccount->id,
            'bank_name' => $bankAccount->bank_name,
            'account_number' => $bankAccount->account_number,
            'account_name' => $bankAccount->account_name,
        ], $ip);
    }

    /**
     * Rút bằng nạp tiền vào số điện thoại hoặc nhận mã thẻ cào, theo mệnh giá admin cấu hình và không thu phí.
     */
    public function requestPhone(User $user, WithdrawalMethod $method, PhoneCarrier $carrier, int $amount, ?string $phoneNumber = null, ?string $ip = null): Withdrawal
    {
        if (! $method->isPhone()) {
            throw new BusinessRuleException('Cách nhận tiền không hợp lệ.');
        }

        if (! $method->isEnabled()) {
            throw new BusinessRuleException("{$method->getLabel()} đang tạm ngưng, vui lòng chọn cách nhận tiền khác.");
        }

        if (! in_array($amount, $this->settings->phoneWithdrawAmounts(), true)) {
            throw new BusinessRuleException('Mệnh giá không hợp lệ.');
        }

        $phoneNumber = $method === WithdrawalMethod::PhoneTopup ? static::normalizePhoneNumber((string) $phoneNumber) : null;

        if ($method === WithdrawalMethod::PhoneTopup && $phoneNumber === null) {
            throw new BusinessRuleException('Số điện thoại không hợp lệ.');
        }

        return $this->open($user, $amount, 0, [
            'method' => $method,
            'phone_carrier' => $carrier,
            'phone_number' => $phoneNumber,
        ], $ip);
    }

    /**
     * "+84 98 123 4567", "84981234567" => "0981234567"; null khi không phải số di động Việt Nam.
     */
    public static function normalizePhoneNumber(string $phoneNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber);

        if (str_starts_with($digits, '84') && strlen($digits) === 11) {
            $digits = '0'.substr($digits, 2);
        }

        return preg_match('/^0[35789]\d{8}$/', $digits) ? $digits : null;
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
        if ($withdrawal->method === WithdrawalMethod::PhoneCard) {
            throw new BusinessRuleException('Yêu cầu đổi thẻ cào cần nhập serial và mã thẻ khi xác nhận.');
        }

        $description = $withdrawal->method === WithdrawalMethod::PhoneTopup
            ? "Đã nạp tiền điện thoại {$withdrawal->code}"
            : "Đã chuyển khoản {$withdrawal->code}";

        $this->pay($withdrawal, $admin, ['bank_txn_ref' => $bankTxnRef], $description);
    }

    /**
     * Thanh toán yêu cầu đổi thẻ cào: lưu serial và mã thẻ (mã hoá) để thành viên xem trong mục Rút tiền.
     */
    public function deliverCard(Withdrawal $withdrawal, User $admin, string $serial, string $code): void
    {
        if ($withdrawal->method !== WithdrawalMethod::PhoneCard) {
            throw new BusinessRuleException("Yêu cầu {$withdrawal->code} không phải yêu cầu đổi thẻ cào.");
        }

        if (trim($serial) === '' || trim($code) === '') {
            throw new BusinessRuleException('Cần nhập đủ serial và mã thẻ.');
        }

        $this->pay($withdrawal, $admin, ['card_serial' => trim($serial), 'card_code' => trim($code)], "Đã gửi thẻ cào {$withdrawal->code}");
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

    /**
     * Khoá số dư cho một yêu cầu rút mới.
     *
     * @param  array<string, mixed>  $destination
     */
    private function open(User $user, int $amount, int $fee, array $destination, ?string $ip): Withdrawal
    {
        if ($this->fraud->hasConfirmedFlag($user)) {
            throw new BusinessRuleException('Tài khoản đang bị tạm khoá rút tiền. Vui lòng liên hệ hỗ trợ.');
        }

        return DB::transaction(function () use ($user, $amount, $fee, $destination, $ip): Withdrawal {
            // Khoá dòng ví để các yêu cầu rút đồng thời của cùng user xếp hàng tuần tự;
            // khoá trên withdrawals không đủ vì khi chưa có yêu cầu nào thì không có dòng để khoá.
            Wallet::query()->whereKey($user->id)->lockForUpdate()->first();

            $openRequests = $user->withdrawals()
                ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing])
                ->count();

            if ($openRequests >= (int) setting('withdraw_max_pending')) {
                throw new BusinessRuleException('Bạn đang có yêu cầu rút tiền chưa xử lý xong.');
            }

            $withdrawal = $user->withdrawals()->create([
                ...$destination,
                'code' => 'RT'.now()->format('ymdHis').random_int(10, 99),
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function pay(Withdrawal $withdrawal, User $admin, array $attributes, string $description): void
    {
        DB::transaction(function () use ($withdrawal, $admin, $attributes, $description): void {
            $this->move($withdrawal, [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing], WithdrawalStatus::Paid, $admin, [
                ...$attributes,
                'paid_at' => now(),
            ]);

            $this->wallet->post($withdrawal->user_id, WalletTransactionType::Payout, WalletBucket::Frozen, -$withdrawal->amount, $withdrawal, $description, $admin->id);
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
