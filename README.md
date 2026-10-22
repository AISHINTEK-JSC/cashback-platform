# Sàn Hoàn Tiền

Website hoàn tiền (cashback) cho Shopee, TikTok Shop, Lazada: thành viên dán link sản phẩm, mua qua link affiliate gắn `sub_id`, hệ thống kéo đơn từ API sàn, chia hoa hồng cho người mua + F1 + F2 và cho rút tiền về ngân hàng.

## Công nghệ

- Laravel 13, PHP 8.3+
- Blade + Livewire 4 + Tailwind CSS 4 cho trang công khai và dashboard thành viên
- Filament 5 cho trang quản trị `/admin`
- MySQL 8 (production), SQLite (dev/test)
- Pest cho test

## Chạy bằng Docker

Chỉ cần Docker Desktop, không cần cài PHP/Node/MySQL:

```bash
docker compose up -d --build
```

| Địa chỉ | Dùng để |
|---|---|
| http://localhost:8001 | Website |
| http://localhost:8001/admin | Trang quản trị |
| http://localhost:8025 | Mailpit, xem email hệ thống gửi (quên mật khẩu...) |
| `localhost:3307` | MySQL (user `cashback` / pass `secret`, DB `cashback`) |

Lần chạy đầu, container `app` tự tạo `APP_KEY`, chạy migrate và seed dữ liệu demo (tài khoản ở mục dưới). Các container:

- `app`: web (FrankenPHP)
- `queue`: gửi thông báo
- `scheduler`: cron đồng bộ đơn, duyệt đơn đến hạn, quét gian lận
- `mysql`, `mailpit`

Lệnh hay dùng:

```bash
docker compose logs -f app                          # xem log
docker compose exec app php artisan orders:sync      # chạy artisan trong container
docker compose up -d --build                         # áp dụng thay đổi code (build lại image)
docker compose down                                  # dừng (giữ dữ liệu)
docker compose down -v                               # dừng và XOÁ toàn bộ dữ liệu MySQL + file upload
```

Đổi cổng khi bị trùng: `APP_PORT=8080 DB_FORWARD_PORT=3308 docker compose up -d`. Tắt seed demo: `SEED_DATABASE=false`. Khi deploy thật, đặt `APP_ENV=production`, `APP_DEBUG=false`, `SEED_DATABASE=false` và đổi mật khẩu MySQL trong `compose.yaml`.

## Chạy local (không Docker)

```bash
composer run setup          # cài package, tạo .env, migrate, build asset
php artisan migrate:fresh --seed
composer run dev            # web server + queue worker + vite
```

Tài khoản demo (mật khẩu `password`): `admin@example.com` (quản trị), `member@example.com` (thành viên có sẵn đơn, F1/F2).

Dùng MySQL: đặt `DB_CONNECTION=mysql` và `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` trong `.env`.

## Production

Cần chạy thêm:

- Scheduler: `* * * * * php artisan schedule:run` (đồng bộ đơn 15 phút/lần, quét lại 60 ngày lúc 3h, duyệt đơn đến hạn mỗi giờ, quét gian lận lúc 4h — xem `routes/console.php`)
- Queue worker: `php artisan queue:work` (gửi thông báo)
- `php artisan storage:link` (ảnh blog, đính kèm ticket)

## Kết nối API sàn

Mỗi sàn là một driver implement `App\Services\Affiliate\AffiliateDriver` (`generateLink`, `fetchOrders`).

| Sàn | Driver | Trạng thái |
|---|---|---|
| Shopee | `ShopeeDriver` | Đã viết theo Shopee Affiliate Open API (GraphQL), cần kiểm tra với tài khoản thật |
| TikTok Shop | `TikTokDriver` | Khung chờ tích hợp |
| Lazada | `LazadaDriver` | Khung chờ tích hợp |
| Dev/demo | `FakeDriver` | Sinh link và đơn giả, tất định theo `sub_id` |

Bật driver thật: đặt `AFFILIATE_SHOPEE_DRIVER=shopee` trong `.env`, rồi nhập `app_id`, `secret` ở Admin → Sàn liên kết (lưu mã hoá).

## Nguyên tắc nghiệp vụ

- **Ví theo sổ cái**: mọi thay đổi số dư đi qua `WalletService::post()`, ghi 1 dòng `wallet_transactions` (chỉ INSERT) và cập nhật `wallets` trong cùng transaction có khoá dòng. Khoá unique `(reference_type, reference_id, type, bucket)` chống cộng tiền 2 lần khi cron chạy lại.
- **Vòng đời đơn**: `pending` (ghi nhận, tiền vào *chờ duyệt*) → `confirmed` (sàn xác nhận) → `approved` (sau `lock_days` của sàn, tiền sang *khả dụng*) → `paid` (đã đối soát). Huỷ ở bất kỳ bước nào sẽ thu hồi tiền (`reversal`). Đồng bộ không bao giờ kéo lùi trạng thái.
- **Tỷ lệ chốt theo đơn**: `commission_rules` có hiệu lực theo thời gian; đơn lưu `commission_rule_id` và `order_rewards.rate` nên đổi tỷ lệ không ảnh hưởng đơn cũ.
- **Rút tiền**: khả dụng → *đang rút* khi tạo yêu cầu; *đang rút* → đã chi khi admin xác nhận chuyển khoản, hoặc hoàn lại khi từ chối/huỷ.

So với file schema gốc, enum `wallet_transactions.type` có thêm `release` (chờ duyệt → khả dụng) và `payout` (đã chuyển khoản) để mỗi bước di chuyển tiền có khoá chống trùng riêng.

## Cấu trúc chính

```
app/Services/            nghiệp vụ: WalletService, Orders/*, WithdrawalService, CheckinService,
                         TrackingLinkService, ReferralService, Fraud/FraudDetector, Affiliate/*
app/Http/Controllers/    trang công khai, Auth/, Dashboard/
app/Livewire/            LinkGenerator, WithdrawForm, CheckinWidget
app/Filament/            trang quản trị
app/Console/Commands/    orders:sync, orders:approve-due, fraud:scan
config/cashback.php      giá trị mặc định của Cài đặt chung + driver theo sàn
```

## Test

```bash
php artisan test --compact
```
