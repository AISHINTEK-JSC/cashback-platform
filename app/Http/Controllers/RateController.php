<?php

namespace App\Http\Controllers;

use App\Models\CommissionRule;
use App\Models\Platform;
use App\Models\UserLevel;
use Illuminate\View\View;

class RateController extends Controller
{
    public function __invoke(): View
    {
        $platforms = Platform::query()->active()->orderBy('id')->get();

        $rules = CommissionRule::query()
            ->whereIn('platform_id', $platforms->pluck('id'))
            ->effectiveAt(now())
            ->orderByRaw('category_code IS NOT NULL')
            ->orderBy('category_code')
            ->get()
            ->groupBy('platform_id');

        return view('rates', [
            'platforms' => $platforms,
            'rules' => $rules,
            'levels' => UserLevel::query()->orderBy('min_approved_orders')->get(),
        ]);
    }
}
