<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Fillable(['category_id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'thumbnail', 'meta_title', 'meta_description', 'status', 'published_at'])]
class Post extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Bấm "Đã đăng" mà bỏ trống ngày đăng thì đăng ngay, tránh bài bị ẩn vì published_at null
        static::saving(function (Post $post): void {
            if ($post->status === PostStatus::Published && ! $post->published_at) {
                $post->published_at = now();
            }
        });

        static::saved(fn () => Cache::forget('seo.sitemap'));
        static::deleted(fn () => Cache::forget('seo.sitemap'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'view_count' => 'integer',
        ];
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', PostStatus::Published)->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published && $this->published_at?->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->status === PostStatus::Published && (bool) $this->published_at?->isFuture();
    }

    public function thumbnailUrl(): ?string
    {
        if (! $this->thumbnail || str_starts_with($this->thumbnail, 'http') || str_starts_with($this->thumbnail, '/')) {
            return $this->thumbnail;
        }

        return Storage::disk('uploads')->url($this->thumbnail);
    }

    /**
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
