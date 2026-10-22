<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['platform_id', 'category_code', 'user_rate', 'f1_rate', 'f2_rate', 'effective_from', 'effective_to'])]
class CommissionRule extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_rate' => 'decimal:2',
            'f1_rate' => 'decimal:2',
            'f2_rate' => 'decimal:2',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
        ];
    }

    /**
     * Quy tắc có hiệu lực tại một thời điểm.
     *
     * @param  Builder<CommissionRule>  $query
     */
    public function scopeEffectiveAt(Builder $query, CarbonInterface $at): void
    {
        $query->where('effective_from', '<=', $at)
            ->where(fn (Builder $q) => $q->whereNull('effective_to')->orWhere('effective_to', '>', $at));
    }

    /**
     * @return BelongsTo<Platform, $this>
     */
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }
}
