<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function __invoke(Request $request, ReferralService $referrals): View
    {
        $user = $request->user();

        return view('dashboard.referrals.index', [
            'user' => $user,
            'summary' => $referrals->summary($user),
            'earnings' => $referrals->earningsByReferral($user),
            'referrals' => $user->referrals()->withCount('referrals')->latest()->paginate(20),
        ]);
    }
}
