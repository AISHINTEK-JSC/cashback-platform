<?php

namespace App\Services;

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Điểm ghi duy nhất vào ví. Mọi thay đổi số dư đi qua post(): ghi 1 dòng sổ cái
 * và cập nhật số dư cache trong cùng transaction, có khoá dòng ví.
 */
class WalletService
{
    /**
     * Các loại giao dịch được tính vào "tổng đã kiếm" khi tiền vào bucket khả dụng.
     */
    private const EARNING_TYPES = [
        WalletTransactionType::Cashback,
        WalletTransactionType::Referral,
        WalletTransactionType::Checkin,
        WalletTransactionType::Bonus,
        WalletTransactionType::Reversal,
    ];

    /**
     * Ghi một bút toán. Trả về null nếu bút toán cho cùng (reference, type, bucket) đã tồn tại,
     * nhờ đó cron/job chạy lại không cộng tiền 2 lần.
     *
     * @throws InsufficientBalanceException
     */
    public function post(
        User|int $user,
        WalletTransactionType $type,
        WalletBucket $bucket,
        int $amount,
        ?Model $reference = null,
        ?string $description = null,
        ?int $createdBy = null,
        bool $allowNegative = false,
    ): ?WalletTransaction {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return DB::transaction(function () use ($userId, $type, $bucket, $amount, $reference, $description, $createdBy, $allowNegative): ?WalletTransaction {
            $wallet = $this->lockWallet($userId);

            if ($reference && $this->alreadyPosted($reference, $type, $bucket)) {
                return null;
            }

            $column = $bucket->column();
            $newBalance = $wallet->{$column} + $amount;

            if ($amount < 0 && $newBalance < 0 && ! $allowNegative) {
                throw InsufficientBalanceException::make();
            }

            $wallet->{$column} = $newBalance;

            if ($bucket === WalletBucket::Available && in_array($type, self::EARNING_TYPES, true)) {
                $wallet->total_earned += $amount;
            }

            if ($type === WalletTransactionType::Payout) {
                $wallet->total_withdrawn -= $amount;
            }

            $wallet->version++;
            $wallet->save();

            return WalletTransaction::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'bucket' => $bucket,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference_type' => $reference?->getTable(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Số tiền đã ghi cho (reference, type, bucket), 0 nếu chưa ghi.
     */
    public function postedAmount(Model $reference, WalletTransactionType $type, WalletBucket $bucket): int
    {
        return (int) WalletTransaction::query()
            ->where('reference_type', $reference->getTable())
            ->where('reference_id', $reference->getKey())
            ->where('type', $type)
            ->where('bucket', $bucket)
            ->value('amount');
    }

    /**
     * Tính lại số dư từ sổ cái, dùng để đối soát với bảng wallets.
     *
     * @return array<string, int>
     */
    public function recomputeFromLedger(int $userId): array
    {
        $sums = WalletTransaction::query()
            ->where('user_id', $userId)
            ->groupBy('bucket')
            ->selectRaw('bucket, SUM(amount) as total')
            ->pluck('total', 'bucket');

        return collect(WalletBucket::cases())
            ->mapWithKeys(fn (WalletBucket $bucket) => [$bucket->column() => (int) ($sums[$bucket->value] ?? 0)])
            ->all();
    }

    private function lockWallet(int $userId): Wallet
    {
        $wallet = Wallet::query()->whereKey($userId)->lockForUpdate()->first();

        if (! $wallet) {
            Wallet::query()->insertOrIgnore(['user_id' => $userId, 'created_at' => now(), 'updated_at' => now()]);
            $wallet = Wallet::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
        }

        return $wallet;
    }

    private function alreadyPosted(Model $reference, WalletTransactionType $type, WalletBucket $bucket): bool
    {
        return WalletTransaction::query()
            ->where('reference_type', $reference->getTable())
            ->where('reference_id', $reference->getKey())
            ->where('type', $type)
            ->where('bucket', $bucket)
            ->exists();
    }
}
