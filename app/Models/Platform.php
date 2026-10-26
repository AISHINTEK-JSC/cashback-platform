<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['code', 'driver', 'name', 'logo', 'api_config', 'lock_days', 'max_commission_rate', 'is_active', 'is_coming_soon'])]
#[Hidden(['api_config'])]
class Platform extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'api_config' => 'encrypted:array',
            'lock_days' => 'integer',
            'max_commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'is_coming_soon' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Platform>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Sàn chưa bật nhưng vẫn hiển thị "Sắp ra mắt" (đang chờ duyệt campaign).
     *
     * @param  Builder<Platform>  $query
     */
    public function scopeComingSoon(Builder $query): void
    {
        $query->where('is_active', false)->where('is_coming_soon', true);
    }

    public function isComingSoon(): bool
    {
        return ! $this->is_active && $this->is_coming_soon;
    }

    /**
     * Logo tải lên ở trang quản trị, chưa có thì dùng logo mặc định trong public/images/platforms.
     */
    public function logoUrl(): ?string
    {
        if ($this->logo) {
            return Storage::disk('public')->url($this->logo);
        }

        return file_exists(public_path("images/platforms/{$this->code}.png")) ? asset("images/platforms/{$this->code}.png") : null;
    }

    /**
     * % hoàn tối đa cho người mua = % hoa hồng tối đa của sàn × % chia cho người mua.
     */
    public function maxCashbackRate(float|string|null $userRate): ?float
    {
        if ($this->max_commission_rate === null || $userRate === null) {
            return null;
        }

        return round((float) $this->max_commission_rate * (float) $userRate / 100, 2);
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->api_config, $key, $default);
    }

    /**
     * @return HasMany<CommissionRule, $this>
     */
    public function commissionRules(): HasMany
    {
        return $this->hasMany(CommissionRule::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<Voucher, $this>
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }
}
