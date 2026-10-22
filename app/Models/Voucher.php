<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['platform_id', 'code', 'title', 'discount_type', 'discount_value', 'min_order', 'max_discount', 'url', 'starts_at', 'expires_at', 'is_active', 'sort_order'])]
class Voucher extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'discount_value' => 'integer',
            'min_order' => 'integer',
            'max_discount' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Voucher đang bật và còn trong thời gian hiệu lực.
     *
     * @param  Builder<Voucher>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function discountLabel(): string
    {
        return match ($this->discount_type) {
            DiscountType::Percent => 'Giảm '.$this->discount_value.'%',
            DiscountType::Fixed => 'Giảm '.vnd($this->discount_value),
            DiscountType::Freeship => 'Freeship '.vnd($this->discount_value),
        };
    }

    /**
     * @return BelongsTo<Platform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }
}
