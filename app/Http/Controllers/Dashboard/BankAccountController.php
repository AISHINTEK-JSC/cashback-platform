<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\UserBankAccount;
use App\Services\ActivityLogger;
use App\Services\Fraud\FraudDetector;
use App\Support\Banks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.bank-accounts.index', [
            'accounts' => $request->user()->bankAccounts()->orderByDesc('is_default')->latest()->get(),
            'banks' => Banks::all(),
        ]);
    }

    public function store(Request $request, FraudDetector $fraud, ActivityLogger $activity): RedirectResponse
    {
        $data = $request->validate([
            'bank_code' => ['required', Rule::in(array_keys(Banks::all()))],
            'account_number' => ['required', 'string', 'regex:/^[0-9]{6,20}$/'],
            'account_name' => ['required', 'string', 'max:100'],
        ], [
            'account_number.regex' => 'Số tài khoản chỉ gồm 6-20 chữ số.',
        ]);

        $user = $request->user();

        if ($user->bankAccounts()->count() >= 5) {
            return back()->with('error', 'Tối đa 5 tài khoản nhận tiền.');
        }

        $account = DB::transaction(fn () => $user->bankAccounts()->create([
            'bank_code' => $data['bank_code'],
            'bank_name' => Banks::all()[$data['bank_code']],
            'account_number' => $data['account_number'],
            'account_name' => Str::upper(Str::ascii($data['account_name'])),
            'is_default' => ! $user->bankAccounts()->exists(),
        ]));

        $activity->log($user, 'bank.create', ['bank_account_id' => $account->id, 'bank_code' => $account->bank_code]);
        $fraud->checkSharedBankAccount($account);

        return back()->with('success', 'Đã thêm tài khoản nhận tiền.');
    }

    public function makeDefault(Request $request, UserBankAccount $bankAccount): RedirectResponse
    {
        abort_unless($bankAccount->user_id === $request->user()->id, 404);

        DB::transaction(function () use ($request, $bankAccount): void {
            $request->user()->bankAccounts()->update(['is_default' => false]);
            $bankAccount->update(['is_default' => true]);
        });

        return back()->with('success', 'Đã đặt làm tài khoản mặc định.');
    }

    public function destroy(Request $request, UserBankAccount $bankAccount, ActivityLogger $activity): RedirectResponse
    {
        abort_unless($bankAccount->user_id === $request->user()->id, 404);

        $inUse = $request->user()->withdrawals()
            ->where('bank_account_id', $bankAccount->id)
            ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved, WithdrawalStatus::Processing])
            ->exists();

        if ($inUse) {
            return back()->with('error', 'Tài khoản đang dùng cho yêu cầu rút tiền chưa xử lý xong.');
        }

        $bankAccount->delete();
        $activity->log($request->user(), 'bank.delete', ['bank_account_id' => $bankAccount->id]);

        return back()->with('success', 'Đã xoá tài khoản nhận tiền.');
    }
}
