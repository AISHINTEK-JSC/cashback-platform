<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'user_id', 'bank_account_id', 'bank_name', 'account_number', 'account_name', 'amount', 'fee', 'net_amount', 'status', 'reviewed_by', 'reviewed_at', 'reject_reason', 'bank_txn_ref', 'paid_at', 'request_ip'])]
class Withdrawal extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WithdrawalStatus::class,
            'amount' => 'integer',
            'fee' => 'integer',
            'net_amount' => 'integer',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
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
     * @return BelongsTo<UserBankAccount, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(UserBankAccount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
