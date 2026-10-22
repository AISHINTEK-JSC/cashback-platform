<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\WalletBucket;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate(['vi' => ['nullable', Rule::enum(WalletBucket::class)]]);

        return view('dashboard.wallet.index', [
            'wallet' => $request->user()->wallet,
            'transactions' => $request->user()->walletTransactions()
                ->when($filters['vi'] ?? null, fn ($q, $bucket) => $q->where('bucket', $bucket))
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }
}
