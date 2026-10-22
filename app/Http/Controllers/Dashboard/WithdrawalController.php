<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\ActivityLogger;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.withdrawals.index', [
            'withdrawals' => $request->user()->withdrawals()->latest('id')->paginate(15),
        ]);
    }

    public function cancel(Request $request, Withdrawal $withdrawal, WithdrawalService $service, ActivityLogger $activity): RedirectResponse
    {
        $service->cancelByUser($withdrawal, $request->user());
        $activity->log($request->user(), 'withdraw.cancel', ['withdrawal_id' => $withdrawal->id]);

        return back()->with('success', "Đã huỷ yêu cầu {$withdrawal->code}, tiền đã hoàn về số dư khả dụng.");
    }
}
