<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'bank_code', 'bank_name', 'account_number', 'account_name', 'is_default', 'verified_at'])]
class UserBankAccount extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'verified_at' => 'datetime',
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
     * Thời điểm tài khoản mới thêm bắt đầu nhận được tiền rút; null khi đã hết thời gian khoá.
     */
    public function withdrawableFrom(): ?Carbon
    {
        $availableAt = $this->created_at?->copy()->addHours((int) setting('withdraw_new_bank_hold_hours'));

        return $availableAt?->isFuture() ? $availableAt : null;
    }

    public function maskedNumber(): string
    {
        return str_repeat('•', max(0, strlen($this->account_number) - 4)).substr($this->account_number, -4);
    }
}
