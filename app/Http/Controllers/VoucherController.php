<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __invoke(Request $request): View
    {
        $platforms = Platform::query()->active()->orderBy('id')->get();
        $current = $platforms->firstWhere('code', $request->query('san'));

        $vouchers = Voucher::query()
            ->available()
            ->with('platform')
            ->when($current, fn ($q) => $q->where('platform_id', $current->id))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('vouchers.index', compact('platforms', 'current', 'vouchers'));
    }
}
