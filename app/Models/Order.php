<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['platform_id', 'platform_order_id', 'user_id', 'tracking_link_id', 'sub_id', 'order_amount', 'commission_amount', 'cashback_amount', 'commission_rule_id', 'status', 'platform_status', 'purchased_at', 'confirmed_at', 'approved_at', 'cancelled_at', 'raw_payload', 'last_synced_at'])]
class Order extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_amount' => 'integer',
            'commission_amount' => 'integer',
            'cashback_amount' => 'integer',
            'status' => OrderStatus::class,
            'purchased_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'raw_payload' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Ngày dự kiến tiền chuyển sang "có thể rút", hiển thị trên timeline của user.
     */
    public function expectedAvailableAt(): ?CarbonInterface
    {
        if ($this->approved_at) {
            return $this->approved_at;
        }

        return $this->purchased_at?->copy()->addDays($this->platform->lock_days);
    }

    /**
     * @return BelongsTo<Platform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<TrackingLink, $this>
     */
    public function trackingLink(): BelongsTo
    {
        return $this->belongsTo(TrackingLink::class);
    }

    /**
     * @return BelongsTo<CommissionRule, $this>
     */
    public function commissionRule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('id');
    }

    /**
     * @return HasMany<OrderReward, $this>
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(OrderReward::class);
    }
}
