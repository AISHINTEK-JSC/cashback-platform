<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Services\ActivityLogger;
use App\Services\WithdrawalService;
use Illuminate\View\View;
use Livewire\Component;

class WithdrawForm extends Component
{
    public ?int $bankAccountId = null;

    public ?int $amount = null;

    public function mount(): void
    {
        $this->bankAccountId = auth()->user()->bankAccounts()->where('is_default', true)->value('id')
            ?? auth()->user()->bankAccounts()->value('id');
    }

    public function withdrawAll(): void
    {
        $this->amount = max(0, auth()->user()->wallet->available_balance);
    }

    public function submit(WithdrawalService $service, ActivityLogger $activity): void
    {
        $user = auth()->user();

        $this->validate([
            'bankAccountId' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
        ], [
            'bankAccountId.required' => 'Hãy chọn tài khoản nhận tiền.',
            'amount.required' => 'Hãy nhập số tiền muốn rút.',
        ]);

        $bankAccount = $user->bankAccounts()->find($this->bankAccountId);

        if (! $bankAccount) {
            $this->addError('bankAccountId', 'Tài khoản nhận tiền không hợp lệ.');

            return;
        }

        try {
            $withdrawal = $service->request($user, $bankAccount, (int) $this->amount, request()->ip());
        } catch (BusinessRuleException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        $activity->log($user, 'withdraw.create', ['withdrawal_id' => $withdrawal->id, 'amount' => $withdrawal->amount]);

        session()->flash('success', "Đã tạo yêu cầu rút tiền {$withdrawal->code}. Chúng tôi sẽ xử lý trong 1-3 ngày làm việc.");
        $this->redirectRoute('dashboard.withdrawals.index');
    }

    public function render(WithdrawalService $service): View
    {
        $amount = max(0, (int) $this->amount);

        return view('livewire.withdraw-form', [
            'wallet' => auth()->user()->wallet()->first(),
            'bankAccounts' => auth()->user()->bankAccounts()->orderByDesc('is_default')->get(),
            'fee' => $amount > 0 ? $service->fee($amount) : 0,
            'min' => (int) setting('withdraw_min'),
        ]);
    }
}
