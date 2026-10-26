<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        return view('posts.index', [
            'posts' => Post::query()->published()->with('category')->latest('published_at')->paginate(12),
            'categories' => PostCategory::query()->orderBy('name')->get(),
            'currentCategory' => null,
        ]);
    }

    public function category(PostCategory $category): View
    {
        return view('posts.index', [
            'posts' => $category->posts()->published()->with('category')->latest('published_at')->paginate(12),
            'categories' => PostCategory::query()->orderBy('name')->get(),
            'currentCategory' => $category,
        ]);
    }

    public function show(Post $post): View
    {
        // Bản nháp, bài hẹn giờ chỉ quản trị viên/nhân viên xem trước được
        $isPreview = ! $post->isPublished();
        abort_if($isPreview && ! auth()->user()?->isStaff(), 404);

        if (! $isPreview) {
            $post->incrementQuietly('view_count');
        }

        return view('posts.show', [
            'post' => $post->load('category', 'author'),
            'isPreview' => $isPreview,
            'related' => Post::query()->published()->with('category')
                ->whereKeyNot($post->id)
                ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
