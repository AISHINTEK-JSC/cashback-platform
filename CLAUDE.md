@AGENTS.md

# Ghi chú dự án

- Xem README.md cho kiến trúc và nguyên tắc nghiệp vụ.
- Không bao giờ ghi trực tiếp vào `wallets` hoặc sửa/xoá `wallet_transactions`; mọi thay đổi tiền đi qua `App\Services\WalletService::post()`.
- Chuyển trạng thái đơn qua `OrderStatusService::transition()` (ghi lịch sử và xử lý tiền đi kèm), rút tiền qua `WithdrawalService`.
- Livewire dùng component dạng class (`app/Livewire`), đã cấu hình trong `config/livewire.php`.
- Trong closure của Filament, đặt tên tham số đúng quy ước (`$query`, `$record`, `$data`, `$state`...) vì Filament inject theo tên.
- Chuỗi hiển thị cho người dùng viết bằng tiếng Việt; nhãn enum nằm trong `app/Enums`.
