<?php

namespace App\Console\Commands;

use App\Services\Orders\OrderStatusService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:approve-due')]
#[Description('Duyệt các đơn đã được sàn xác nhận và hết thời gian chờ chốt, chuyển tiền sang khả dụng')]
class ApproveDueOrders extends Command
{
    public function handle(OrderStatusService $statuses): int
    {
        $count = $statuses->approveDueOrders();

        $this->components->info("Đã duyệt {$count} đơn.");

        return self::SUCCESS;
    }
}
