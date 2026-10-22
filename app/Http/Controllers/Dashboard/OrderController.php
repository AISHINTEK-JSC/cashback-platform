<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Platform;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'trang_thai' => ['nullable', Rule::enum(OrderStatus::class)],
            'san' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:64'],
        ]);

        $orders = $request->user()->orders()
            ->with('platform')
            ->when($filters['trang_thai'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['san'] ?? null, fn ($q, $code) => $q->whereRelation('platform', 'code', $code))
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where('platform_order_id', 'like', "%{$term}%"))
            ->latest('purchased_at')
            ->paginate(20)
            ->withQueryString();

        $totals = $request->user()->orders()
            ->selectRaw('status, COUNT(*) as orders_count, SUM(cashback_amount) as cashback_total')
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->status->value);

        return view('dashboard.orders.index', [
            'orders' => $orders,
            'totals' => $totals,
            'platforms' => Platform::query()->active()->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return view('dashboard.orders.show', [
            'order' => $order->load('platform', 'items', 'statusHistories', 'trackingLink'),
        ]);
    }
}
