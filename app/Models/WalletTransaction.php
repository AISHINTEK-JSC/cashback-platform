<?php

namespace App\Models;

use App\Enums\WalletBucket;
use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Sổ cái: chỉ INSERT, không UPDATE/DELETE.
 */
#[Fillable(['user_id', 'type', 'bucket', 'amount', 'balance_after', 'reference_type', 'reference_id', 'description', 'created_by'])]
class WalletTransaction extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Wallet transactions are immutable.'));
        static::deleting(fn () => throw new LogicException('Wallet transactions are immutable.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'bucket' => WalletBucket::class,
            'amount' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
