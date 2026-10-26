<?php

namespace Database\Seeders;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Bộ bài viết SEO ban đầu, nội dung nằm ở database/seeders/posts/{slug}.html,
 * ảnh bìa ở posts/{slug}.jpg trên disk "uploads" (Cloudflare R2 ở production).
 *
 * Khoá theo slug và chỉ tạo bài chưa có, nên chạy lại an toàn trên production
 * và không ghi đè bài admin đã chỉnh sửa; bài đã có mà chưa có ảnh bìa thì được gắn ảnh.
 * php artisan db:seed --class=BlogPostSeeder
 */
class BlogPostSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const CATEGORIES = [
        'huong-dan-hoan-tien' => 'Hướng dẫn hoàn tiền',
        'meo-san-sale' => 'Mẹo săn sale',
        'kiem-tien-affiliate' => 'Kiếm tiền affiliate',
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
        [
            'slug' => 'affiliate-marketing-la-gi',
            'category' => 'kiem-tien-affiliate',
            'title' => 'Affiliate marketing là gì? Hướng dẫn kiếm tiền tiếp thị liên kết cho người mới',
            'meta_title' => 'Affiliate marketing là gì? Cách kiếm tiền tiếp thị liên kết',
            'excerpt' => 'Affiliate hoạt động thế nào, hoa hồng được tính ra sao, có những cách làm affiliate nào và người mới nên bắt đầu từ đâu.',
            'meta_description' => 'Affiliate marketing (tiếp thị liên kết) là gì, cách tính hoa hồng CPS, CPA, các kênh làm affiliate và 5 bước bắt đầu kiếm tiền cho người mới.',
        ],
        [
            'slug' => 'mua-hang-tiktok-shop-duoc-hoan-tien',
            'category' => 'huong-dan-hoan-tien',
            'title' => 'Mua hàng TikTok Shop được hoàn tiền: hướng dẫn chi tiết từng bước',
            'meta_title' => 'Cách mua hàng TikTok Shop được hoàn tiền',
            'excerpt' => 'Cách lấy link sản phẩm TikTok Shop, tạo link hoàn tiền và những lưu ý riêng để đơn TikTok Shop được ghi nhận đầy đủ.',
            'meta_description' => 'Hướng dẫn mua hàng TikTok Shop được hoàn tiền: lấy link sản phẩm, tạo link hoàn tiền, kết hợp mã giảm giá và lưu ý khi mua trong livestream.',
        ],
        [
            'slug' => 'cach-dang-ky-shopee-affiliate',
            'category' => 'kiem-tien-affiliate',
            'title' => 'Cách đăng ký Shopee Affiliate và bắt đầu ra đơn cho người mới',
            'meta_title' => 'Cách đăng ký Shopee Affiliate cho người mới',
            'excerpt' => 'Quy trình đăng ký chương trình tiếp thị liên kết Shopee, cách để dễ được duyệt, tạo link, xem báo cáo và mẹo ra đơn đầu tiên.',
            'meta_description' => 'Hướng dẫn đăng ký Shopee Affiliate từng bước, mẹo để được duyệt, cách tạo link affiliate, theo dõi báo cáo, nhận hoa hồng và ra đơn đầu tiên.',
        ],
        [
            'slug' => 'meo-tiet-kiem-khi-mua-sam-online',
            'category' => 'meo-san-sale',
            'title' => '12 mẹo tiết kiệm khi mua sắm online ai cũng nên biết',
            'meta_title' => '12 mẹo tiết kiệm khi mua sắm online',
            'excerpt' => 'Những thói quen đơn giản trước, trong và sau khi mua giúp bạn tiêu ít hơn mà vẫn mua đúng thứ cần.',
            'meta_description' => '12 mẹo tiết kiệm khi mua sắm online: quy tắc 24 giờ, so sánh giá, săn mã giảm giá, mua qua link hoàn tiền, gom đơn freeship và hơn thế nữa.',
        ],
        [
            'slug' => 'cach-tranh-mua-hang-gia-tren-tiktok-shop',
            'category' => 'meo-san-sale',
            'title' => '8 dấu hiệu giúp tránh mua hàng giả trên TikTok Shop',
            'meta_title' => 'Cách tránh mua hàng giả trên TikTok Shop',
            'excerpt' => 'Kiểm tra shop, giá, đánh giá, mô tả và chính sách đổi trả trước khi chốt đơn qua video hay livestream.',
            'meta_description' => '8 dấu hiệu nhận biết shop bán hàng giả trên TikTok Shop và cách tự bảo vệ khi mua qua video, livestream: kiểm tra shop, giá, đánh giá, đổi trả.',
        ],
        [
            'slug' => 'lam-affiliate-tiktok-shop',
            'category' => 'kiem-tien-affiliate',
            'title' => 'Làm affiliate TikTok Shop: cách gắn giỏ hàng, chọn sản phẩm và làm video ra đơn',
            'meta_title' => 'Làm affiliate TikTok Shop cho người mới',
            'excerpt' => 'Các bước bắt đầu làm tiếp thị liên kết TikTok Shop, cách chọn sản phẩm dễ bán và mẹo làm video có người mua.',
            'meta_description' => 'Hướng dẫn làm affiliate TikTok Shop: điều kiện tham gia, gắn giỏ hàng vào video, chọn sản phẩm dễ ra đơn, mẹo làm video và những điều cần tránh.',
        ],
        [
            'slug' => 'shopee-xu-la-gi',
            'category' => 'meo-san-sale',
            'title' => 'Shopee Xu là gì? Cách kiếm và dùng Xu để giảm tiền đơn hàng',
            'meta_title' => 'Shopee Xu là gì? Cách kiếm và dùng Shopee Xu',
            'excerpt' => 'Giải thích Shopee Xu, các cách tích Xu mỗi ngày, cách dùng Xu khi thanh toán và những lưu ý để không bị mất Xu.',
            'meta_description' => 'Shopee Xu là gì, cách kiếm Xu mỗi ngày, cách dùng Xu trừ tiền khi thanh toán, hạn sử dụng Xu và cách kết hợp với mã giảm giá.',
        ],
        [
            'slug' => 'cach-chon-size-giay-adidas-khi-mua-online',
            'category' => 'meo-san-sale',
            'title' => 'Cách chọn size giày Adidas khi mua online và mẹo mua giá tốt',
            'meta_title' => 'Cách chọn size giày Adidas khi mua online',
            'excerpt' => 'Đo chân đúng cách, đọc bảng size Adidas, tham khảo form giày và các cách mua giày Adidas chính hãng giá tốt hơn.',
            'meta_description' => 'Hướng dẫn đo chân và chọn size giày Adidas khi mua online, đọc bảng size UK/US/EU, mẹo mua giày chính hãng giá tốt kèm hoàn tiền.',
        ],
        [
            'slug' => 'sai-lam-khi-moi-lam-affiliate',
            'category' => 'kiem-tien-affiliate',
            'title' => '7 sai lầm khi mới làm affiliate khiến bạn mãi không ra đơn',
            'meta_title' => '7 sai lầm khi mới làm affiliate',
            'excerpt' => 'Rải link khắp nơi, chọn sản phẩm vì hoa hồng, bỏ cuộc quá sớm... và cách khắc phục từng sai lầm.',
            'meta_description' => '7 sai lầm phổ biến khi mới làm affiliate marketing: spam link, chọn sai sản phẩm, không chọn ngách, không đo lường... và cách khắc phục.',
        ],
        [
            'slug' => 'cach-kiem-them-tien-tren-vi-hoan-tien',
            'category' => 'huong-dan-hoan-tien',
            'title' => '4 cách nhận thêm tiền trên Ví Hoàn Tiền ngoài hoàn tiền mua sắm',
            'meta_title' => '4 cách nhận thêm tiền trên Ví Hoàn Tiền',
            'excerpt' => 'Giới thiệu bạn bè nhận hoa hồng F1, F2, điểm danh mỗi ngày, lên hạng thành viên và kết hợp mã giảm giá.',
            'meta_description' => 'Kiếm thêm tiền trên Ví Hoàn Tiền: hoa hồng giới thiệu bạn bè F1, F2, thưởng điểm danh mỗi ngày, cộng thêm theo hạng thành viên.',
        ],
        [
            'slug' => 'meo-san-flash-sale-shopee',
            'category' => 'meo-san-sale',
            'title' => 'Mẹo săn Flash Sale Shopee: canh giờ, chuẩn bị và tránh hết suất',
            'meta_title' => 'Mẹo săn Flash Sale Shopee thành công',
            'excerpt' => 'Flash Sale Shopee hoạt động thế nào, chuẩn bị gì trước khung giờ và thao tác ra sao để không bị hết suất.',
            'meta_description' => 'Cách săn Flash Sale Shopee: các khung giờ, chuẩn bị trước khi mở bán, thao tác nhanh để không hết suất và những lỗi thường gặp.',
        ],
        [
            'slug' => 'kinh-nghiem-san-sale-11-11',
            'category' => 'meo-san-sale',
            'title' => 'Kinh nghiệm săn sale 11.11: kế hoạch 7 ngày để mua rẻ mà không mua thừa',
            'meta_title' => 'Kinh nghiệm săn sale 11.11 mua rẻ không mua thừa',
            'excerpt' => 'Kế hoạch từng ngày trước 11.11: lập danh sách, ghi giá, lưu mã, thứ tự mua để vừa được giảm giá vừa nhận hoàn tiền.',
            'meta_description' => 'Kinh nghiệm săn sale 11.11: kế hoạch 7 ngày, ghi giá trước sale, lưu mã giảm giá, thứ tự mua để nhận hoàn tiền và checklist tải về.',
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
            $thumbnail = Storage::disk('uploads')->exists("posts/{$post['slug']}.jpg") ? "posts/{$post['slug']}.jpg" : null;

            $model = Post::query()->firstOrCreate(['slug' => $post['slug']], [
                'thumbnail' => $thumbnail,
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

            if (! $model->thumbnail && $thumbnail) {
                $model->update(['thumbnail' => $thumbnail]);
            }
        }
    }
}
