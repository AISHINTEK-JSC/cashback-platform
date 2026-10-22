<?php

namespace App\Models;

use App\Enums\WalletBucket;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Số dư cache. Chỉ WalletService được phép ghi, luôn cùng transaction với wallet_transactions.
 */
#[Table(key: 'user_id', incrementing: false)]
#[Fillable(['user_id'])]
class Wallet extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pending_balance' => 'integer',
            'available_balance' => 'integer',
            'frozen_balance' => 'integer',
            'total_earned' => 'integer',
            'total_withdrawn' => 'integer',
            'version' => 'integer',
        ];
    }

    public function balanceOf(WalletBucket $bucket): int
    {
        return (int) $this->{$bucket->column()};
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
