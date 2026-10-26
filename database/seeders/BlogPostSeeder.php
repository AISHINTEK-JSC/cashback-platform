<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Bộ bài viết SEO ban đầu, nội dung nằm ở database/seeders/posts/{slug}.html.
 *
 * Khoá theo slug và chỉ tạo bài chưa có, nên chạy lại an toàn trên production
 * và không ghi đè bài admin đã chỉnh sửa: php artisan db:seed --class=BlogPostSeeder
 */
class BlogPostSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const CATEGORIES = [
        'huong-dan-hoan-tien' => 'Hướng dẫn hoàn tiền',
        'meo-san-sale' => 'Mẹo săn sale',
    ];

    /**
     * Xếp theo thứ tự đăng, bài đầu đăng sớm nhất.
     *
     * @var list<array{slug: string, category: string, title: string, meta_title: string, excerpt: string, meta_description: string}>
     */
    private const POSTS = [
        [
            'slug' => 'hoan-tien-la-gi',
            'category' => 'huong-dan-hoan-tien',
            'title' => 'Hoàn tiền là gì? Cách nhận hoàn tiền khi mua hàng Shopee, TikTok Shop, Lazada',
            'meta_title' => 'Hoàn tiền là gì? Cách nhận hoàn tiền Shopee, TikTok Shop, Lazada',
            'excerpt' => 'Giải thích hoàn tiền (cashback) hoạt động thế nào, vì sao mua qua link lại được trả lại tiền và 4 bước để nhận hoàn tiền cho mỗi đơn hàng.',
            'meta_description' => 'Hoàn tiền (cashback) là gì, vì sao mua qua link được trả lại tiền, bao lâu nhận được tiền và 4 bước nhận hoàn tiền khi mua Shopee, TikTok Shop, Lazada.',
        ],
        [
            'slug' => 'cach-sao-chep-link-san-pham-shopee-tiktok-shop-lazada',
            'category' => 'huong-dan-hoan-tien',
            'title' => 'Cách sao chép link sản phẩm Shopee, TikTok Shop, Lazada trên điện thoại và máy tính',
            'meta_title' => 'Cách copy link sản phẩm Shopee, TikTok Shop, Lazada',
            'excerpt' => 'Hướng dẫn từng bước lấy link sản phẩm trên ứng dụng và website của Shopee, TikTok Shop, Lazada để tạo link hoàn tiền.',
            'meta_description' => 'Hướng dẫn copy link sản phẩm Shopee, TikTok Shop, Lazada trên điện thoại và máy tính, kèm cách tạo link hoàn tiền từ link vừa sao chép.',
        ],
        [
            'slug' => 'cach-san-ma-giam-gia-shopee',
            'category' => 'meo-san-sale',
            'title' => 'Cách săn mã giảm giá Shopee: các loại mã và mẹo kết hợp để giảm nhiều nhất',
            'meta_title' => 'Cách săn mã giảm giá Shopee và kết hợp mã giảm nhiều nhất',
            'excerpt' => 'Phân biệt mã Shopee, mã shop, mã freeship, mã hoàn xu, nơi lấy mã và cách kết hợp mã để giảm nhiều nhất, cộng thêm hoàn tiền.',
            'meta_description' => 'Các loại mã giảm giá Shopee, nơi lấy mã, cách kết hợp mã shop, mã Shopee, mã freeship để giảm nhiều nhất và những lỗi khiến không áp dụng được mã.',
        ],
        [
            'slug' => 'vi-sao-don-hang-khong-duoc-ghi-nhan-hoan-tien',
            'category' => 'huong-dan-hoan-tien',
            'title' => '7 lý do đơn hàng không được ghi nhận hoàn tiền và cách khắc phục',
            'meta_title' => 'Vì sao đơn hàng không được ghi nhận hoàn tiền?',
            'excerpt' => 'Những nguyên nhân thường gặp khiến đơn mua qua link hoàn tiền không hiện trong tài khoản, cách tránh và cách gửi yêu cầu kiểm tra.',
            'meta_description' => '7 nguyên nhân khiến đơn mua qua link không được ghi nhận hoàn tiền: bấm link khác, giỏ hàng có sẵn, mua quá lâu sau khi bấm link... và cách khắc phục.',
        ],
        [
            'slug' => 'lich-sale-shopee-lazada-tiktok-shop',
            'category' => 'meo-san-sale',
            'title' => 'Lịch sale Shopee, Lazada, TikTok Shop trong năm: ngày nào mua rẻ nhất?',
            'meta_title' => 'Lịch sale Shopee, Lazada, TikTok Shop trong năm',
            'excerpt' => 'Tổng hợp các đợt sale ngày đôi, giữa tháng, cuối tháng và các dịp lớn trong năm, kèm danh sách việc cần chuẩn bị trước ngày sale.',
            'meta_description' => 'Lịch sale ngày đôi, sale giữa tháng, cuối tháng, Tết, Black Friday trên Shopee, Lazada, TikTok Shop và cách chuẩn bị để mua rẻ nhất.',
        ],
    ];

    public function run(): void
    {
        $author = User::query()->where('role', UserRole::Admin)->oldest('id')->first();

        if (! $author) {
            $this->command?->warn('Chưa có tài khoản quản trị để đứng tên bài viết, bỏ qua.');

            return;
        }

        $categories = collect(self::CATEGORIES)->map(
            fn (string $name, string $slug) => PostCategory::query()->firstOrCreate(['slug' => $slug], ['name' => $name]),
        );

        foreach (self::POSTS as $index => $post) {
            Post::query()->firstOrCreate(['slug' => $post['slug']], [
                'category_id' => $categories[$post['category']]->id,
                'author_id' => $author->id,
                'title' => $post['title'],
                'meta_title' => $post['meta_title'],
                'excerpt' => $post['excerpt'],
                'meta_description' => $post['meta_description'],
                'content' => file_get_contents(database_path("seeders/posts/{$post['slug']}.html")),
                'status' => PostStatus::Published,
                'published_at' => now()->subDays((count(self::POSTS) - $index) * 2),
            ]);
        }
    }
}
