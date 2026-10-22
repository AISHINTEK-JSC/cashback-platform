<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\UserLevelService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request, UserLevelService $levels): View
    {
        $user = $request->user()->load('wallet', 'level');

        return view('dashboard.index', [
            'user' => $user,
            'wallet' => $user->wallet,
            'recentOrders' => $user->orders()->with('platform')->latest('purchased_at')->limit(5)->get(),
            'approvedOrders' => $levels->approvedOrderCount($user),
            'nextLevel' => $levels->nextLevel($user),
        ]);
    }
}
