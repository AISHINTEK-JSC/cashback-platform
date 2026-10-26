<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * sitemap.xml và robots.txt cho công cụ tìm kiếm.
 */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('seo.sitemap', now()->addHour(), function (): string {
            $latestPost = Post::query()->published()->max('updated_at');

            $urls = collect([
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('vouchers.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ...Platform::query()->active()->orderBy('id')->get()->map(fn (Platform $platform) => [
                    'loc' => route('vouchers.platform', $platform),
                    'priority' => '0.9',
                    'changefreq' => 'daily',
                ]),
                ['loc' => route('rates'), 'priority' => '0.8', 'changefreq' => 'weekly'],
                ['loc' => route('posts.index'), 'lastmod' => $latestPost, 'priority' => '0.8', 'changefreq' => 'daily'],
                ...PostCategory::query()->whereHas('posts', fn ($query) => $query->published())->get()->map(fn (PostCategory $category) => [
                    'loc' => route('posts.category', $category),
                    'priority' => '0.6',
                    'changefreq' => 'weekly',
                ]),
                ...Post::query()->published()->latest('published_at')->get(['slug', 'updated_at'])->map(fn (Post $post) => [
                    'loc' => route('posts.show', $post),
                    'lastmod' => $post->updated_at,
                    'priority' => '0.7',
                    'changefreq' => 'monthly',
                ]),
                ['loc' => route('pages.guide'), 'priority' => '0.6', 'changefreq' => 'monthly'],
                ['loc' => route('pages.faq'), 'priority' => '0.6', 'changefreq' => 'monthly'],
                ['loc' => route('pages.terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
                ['loc' => route('pages.privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ]);

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /tai-khoan',
            'Disallow: /admin',
            'Disallow: /go/',
            'Disallow: /r/',
            'Disallow: /auth/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
