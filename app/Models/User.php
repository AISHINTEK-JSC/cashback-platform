<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'phone', 'password', 'avatar', 'referred_by', 'level_id', 'role', 'status', 'email_verified_at', 'last_login_at', 'last_login_ip', 'telegram_chat_id', 'telegram_username', 'telegram_linked_at', 'telegram_prompt_dismissed_at', 'telegram_notifications'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->referral_code ??= static::generateReferralCode();
        });

        static::created(function (User $user): void {
            $user->wallet()->create();
        });

        // Tài khoản bị xoá thì gỡ Telegram: không gửi thông báo nữa và chat đó liên kết được với tài khoản khác (cột unique)
        static::softDeleted(function (User $user): void {
            if ($user->telegram_chat_id) {
                $user->forceFill(['telegram_chat_id' => null, 'telegram_username' => null, 'telegram_linked_at' => null])->saveQuietly();
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'telegram_chat_id' => 'integer',
            'telegram_linked_at' => 'datetime',
            'telegram_prompt_dismissed_at' => 'datetime',
            'telegram_notifications' => 'boolean',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::withTrashed()->where('referral_code', $code)->exists());

        return $code;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isStaff() && $this->isActive();
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::Staff], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function referralUrl(): string
    {
        return route('referral.landing', $this->referral_code);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * F1: những người do user này mời trực tiếp.
     *
     * @return HasMany<User, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * F2: những người do F1 của user này mời.
     *
     * @return Builder<User>
     */
    public function secondLevelReferrals(): Builder
    {
        return User::query()->whereIn('referred_by', $this->referrals()->select('id'));
    }

    /**
     * @return BelongsTo<UserLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class);
    }

    /**
     * @return HasOne<Wallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * @return HasMany<WalletTransaction, $this>
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * @return HasMany<UserBankAccount, $this>
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(UserBankAccount::class);
    }

    /**
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * @return HasMany<TrackingLink, $this>
     */
    public function trackingLinks(): HasMany
    {
        return $this->hasMany(TrackingLink::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<OrderReward, $this>
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(OrderReward::class);
    }

    /**
     * @return HasMany<Withdrawal, $this>
     */
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * @return HasMany<Checkin, $this>
     */
    public function checkins(): HasMany
    {
        return $this->hasMany(Checkin::class);
    }

    /**
     * @return HasMany<SavedProduct, $this>
     */
    public function savedProducts(): HasMany
    {
        return $this->hasMany(SavedProduct::class);
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * @return HasMany<FraudFlag, $this>
     */
    public function fraudFlags(): HasMany
    {
        return $this->hasMany(FraudFlag::class);
    }

    /**
     * @return HasMany<ActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
