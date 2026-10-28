<?php

namespace App\Models;

use App\Enums\PhoneCarrier;
use App\Enums\WithdrawalMethod;
use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['code', 'user_id', 'method', 'bank_account_id', 'bank_name', 'account_number', 'account_name', 'phone_carrier', 'phone_number', 'amount', 'fee', 'net_amount', 'status', 'reviewed_by', 'reviewed_at', 'reject_reason', 'bank_txn_ref', 'card_serial', 'card_code', 'paid_at', 'request_ip'])]
#[Hidden(['card_code'])]
class Withdrawal extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => WithdrawalMethod::class,
            'phone_carrier' => PhoneCarrier::class,
            'status' => WithdrawalStatus::class,
            'amount' => 'integer',
            'fee' => 'integer',
            'net_amount' => 'integer',
            'card_code' => 'encrypted',
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Nơi nhận tiền: tên ngân hàng/ví, hoặc cách rút bằng điện thoại kèm nhà mạng.
     */
    public function destinationName(): string
    {
        return match ($this->method) {
            WithdrawalMethod::PhoneTopup => "Nạp điện thoại {$this->phone_carrier?->getLabel()}",
            WithdrawalMethod::PhoneCard => "Thẻ cào {$this->phone_carrier?->getLabel()}",
            default => (string) $this->bank_name,
        };
    }

    /**
     * Số tài khoản, số điện thoại được nạp, hoặc null với thẻ cào.
     */
    public function destinationNumber(): ?string
    {
        return match ($this->method) {
            WithdrawalMethod::PhoneTopup => $this->phone_number,
            WithdrawalMethod::PhoneCard => null,
            default => $this->account_number,
        };
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
