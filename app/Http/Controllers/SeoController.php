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
                // Trang "Hoàn tiền <sàn>" của các sàn đang bật (sàn sắp ra mắt bị noindex nên không đưa vào)
                ...Platform::query()->active()->orderBy('id')->get()->map(fn (Platform $platform) => [
                    'loc' => route('platforms.show', $platform),
                    'priority' => '0.9',
                    'changefreq' => 'weekly',
                ]),
                // Chỉ đưa trang voucher của sàn đang có mã, trang trống bị noindex
                ...Platform::query()->active()->whereHas('vouchers', fn ($query) => $query->available())->orderBy('id')->get()->map(fn (Platform $platform) => [
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
                ...(setting('transparency_page') ? [['loc' => route('pages.transparency'), 'priority' => '0.6', 'changefreq' => 'daily']] : []),
                ['loc' => route('pages.terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
                ['loc' => route('pages.privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ]);

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n".view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            // Bot tạo bản xem trước link khi chia sẻ (Facebook, Messenger, X): cho đọc mọi trang
            'User-agent: facebookexternalhit',
            'User-agent: Facebot',
            'User-agent: Twitterbot',
            'Allow: /',
            '',
            'User-agent: *',
            'Disallow: /tai-khoan',
            'Disallow: /super/',
            'Disallow: /go/',
            'Disallow: /r/',
            'Disallow: /auth/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
