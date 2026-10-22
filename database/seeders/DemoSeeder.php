<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\OrderStatusSource;
use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Platform;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\TrackingLink;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Models\Voucher;
use App\Services\Affiliate\Data\PlatformOrderData;
use App\Services\Affiliate\Data\PlatformOrderItemData;
use App\Services\Orders\OrderStatusService;
use App\Services\Orders\OrderSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dữ liệu demo cho môi trường local: tài khoản admin, thành viên có cây F1/F2, đơn hàng ở đủ trạng thái.
 */
class DemoSeeder extends Seeder
{
    public function run(OrderSyncService $sync, OrderStatusService $statuses): void
    {
        if (User::query()->where('email', 'member@example.com')->exists()) {
            return;
        }

        $admin = User::query()->firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Quản trị viên',
            'password' => 'password',
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $f2 = $this->member('Lê Văn C', 'f2@example.com');
        $f1 = $this->member('Trần Thị B', 'f1@example.com', $f2);
        $member = $this->member('Nguyễn Văn A', 'member@example.com', $f1, '0900000001');

        foreach (['Phạm Minh D', 'Hoàng Thu E', 'Vũ Đức F', 'Đặng Ngọc G', 'Bùi Anh H'] as $i => $name) {
            $this->member($name, 'friend'.($i + 1).'@example.com', $member);
        }

        UserBankAccount::query()->create([
            'user_id' => $member->id,
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '0011001234567',
            'account_name' => 'NGUYEN VAN A',
            'is_default' => true,
        ]);

        $shopee = Platform::query()->where('code', 'shopee')->firstOrFail();
        $tiktok = Platform::query()->where('code', 'tiktok')->firstOrFail();

        $orders = [
            [$shopee, 'Tai nghe Bluetooth chống ồn', 459000, 36720, OrderStatus::Pending, 2],
            [$shopee, 'Áo thun cotton oversize', 189000, 15120, OrderStatus::Confirmed, 12],
            [$tiktok, 'Serum dưỡng da vitamin C', 320000, 32000, OrderStatus::Confirmed, 20],
            [$shopee, 'Nồi chiên không dầu 5L', 1290000, 64500, 'approve', 50],
            [$tiktok, 'Giày chạy bộ nam', 750000, 60000, 'approve', 60],
            [$shopee, 'Ốp lưng điện thoại', 59000, 4720, OrderStatus::Cancelled, 8],
        ];

        foreach ($orders as $i => [$platform, $name, $amount, $commission, $status, $daysAgo]) {
            $url = 'https://shopee.vn/'.Str::slug($name).'-i.'.(1000 + $i).'.'.(200000 + $i);
            $link = new TrackingLink([
                'user_id' => $member->id,
                'platform_id' => $platform->id,
                'sub_id' => 'demo'.Str::lower(Str::random(12)),
                'original_url' => $url,
                'product_name' => $name,
                'price' => $amount,
                'estimated_rate' => 5.6,
                'affiliate_url' => $url,
                // 0 để FakeDriver không sinh thêm đơn giả trùng với đơn demo bên dưới
                'click_count' => 0,
            ]);
            $link->created_at = now()->subDays($daysAgo);
            $link->save();

            $data = new PlatformOrderData(
                platformOrderId: 'DEMO'.now()->format('ymd').str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                subId: $link->sub_id,
                orderAmount: $amount,
                commissionAmount: $commission,
                status: $status === 'approve' ? OrderStatus::Confirmed : $status,
                platformStatus: 'DEMO',
                purchasedAt: now()->subDays($daysAgo),
                items: [new PlatformOrderItemData(Str::random(8), $name, null, 1, $amount, round($commission / $amount * 100, 2), $commission)],
            );

            $sync->upsert($platform, $data);

            if ($status === 'approve') {
                $order = $member->orders()->where('platform_order_id', $data->platformOrderId)->firstOrFail();
                $statuses->transition($order, OrderStatus::Approved, OrderStatusSource::System, 'Demo');
            }
        }

        foreach ([
            [$shopee, 'SHOPEE50K', 'Giảm 50K cho đơn từ 300K', DiscountType::Fixed, 50000, 300000],
            [$shopee, 'FSVIP', 'Freeship tối đa 30K', DiscountType::Freeship, 30000, 0],
            [$tiktok, 'TTS15', 'Giảm 15% tối đa 40K', DiscountType::Percent, 15, 99000],
        ] as [$platform, $code, $title, $type, $value, $min]) {
            Voucher::query()->firstOrCreate(['code' => $code], [
                'platform_id' => $platform->id,
                'title' => $title,
                'discount_type' => $type,
                'discount_value' => $value,
                'min_order' => $min,
                'expires_at' => now()->addDays(7),
                'is_active' => true,
            ]);
        }

        $category = PostCategory::query()->firstOrCreate(['slug' => 'meo-san-sale'], ['name' => 'Mẹo săn sale']);
        foreach ([
            'Cách săn sale 10.10 trên Shopee để vừa rẻ vừa được hoàn tiền',
            '5 lỗi khiến đơn hàng không được ghi nhận hoàn tiền',
            'Hướng dẫn rút tiền hoàn về ngân hàng trong 1 phút',
        ] as $i => $title) {
            Post::query()->create([
                'category_id' => $category->id,
                'author_id' => $admin->id,
                'title' => $title,
                'slug' => Str::slug($title),
                'excerpt' => 'Mẹo nhỏ giúp bạn mua sắm tiết kiệm hơn và nhận đủ tiền hoàn cho mỗi đơn hàng.',
                'content' => '<p>Nội dung demo. Soạn bài viết thật trong trang quản trị, mục Nội dung → Bài viết.</p>',
                'status' => PostStatus::Published,
                'published_at' => now()->subDays(3 - $i),
            ]);
        }
    }

    private function member(string $name, string $email, ?User $referrer = null, ?string $phone = null): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => 'password',
            'referred_by' => $referrer?->id,
            'email_verified_at' => now(),
        ]);
    }
}
