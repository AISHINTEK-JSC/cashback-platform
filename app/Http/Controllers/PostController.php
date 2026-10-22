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
        abort_unless(Post::query()->published()->whereKey($post->id)->exists(), 404);

        $post->incrementQuietly('view_count');

        return view('posts.show', [
            'post' => $post->load('category', 'author'),
            'related' => Post::query()->published()->with('category')
                ->whereKeyNot($post->id)
                ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }
}
