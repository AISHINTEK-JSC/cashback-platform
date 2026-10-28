<?php

namespace App\Livewire;

use App\Enums\PhoneCarrier;
use App\Enums\WithdrawalMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\Withdrawal;
use App\Services\ActivityLogger;
use App\Services\WithdrawalService;
use App\Support\Settings;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class WithdrawForm extends Component
{
    public string $method = 'bank';

    public ?int $bankAccountId = null;

    public ?int $amount = null;

    public ?string $carrier = null;

    public ?string $phoneNumber = null;

    public function mount(): void
    {
        $this->bankAccountId = auth()->user()->bankAccounts()->where('is_default', true)->value('id')
            ?? auth()->user()->bankAccounts()->value('id');
        $this->phoneNumber = auth()->user()->phone;
    }

    /**
     * Số tiền nhập tay của chuyển khoản và mệnh giá của nạp điện thoại không dùng lẫn cho nhau.
     */
    public function updatedMethod(): void
    {
        $this->reset('amount');
        $this->resetErrorBag();
    }

    public function withdrawAll(): void
    {
        $this->amount = max(0, auth()->user()->wallet->available_balance);
    }

    public function submit(WithdrawalService $service, ActivityLogger $activity): void
    {
        $user = auth()->user();
        $method = WithdrawalMethod::tryFrom($this->method);

        if (! $method || ! in_array($method, $this->availableMethods(), true)) {
            $this->addError('method', 'Cách nhận tiền không hợp lệ.');

            return;
        }

        $withdrawal = $method === WithdrawalMethod::Bank
            ? $this->requestBankTransfer($service)
            : $this->requestPhonePayout($service, $method);

        if (! $withdrawal) {
            return;
        }

        $activity->log($user, 'withdraw.create', ['withdrawal_id' => $withdrawal->id, 'amount' => $withdrawal->amount, 'method' => $method->value]);

        session()->flash('success', "Đã tạo yêu cầu rút tiền {$withdrawal->code}. Chúng tôi sẽ xử lý trong 1-3 ngày làm việc.");
        $this->redirectRoute('dashboard.withdrawals.index');
    }

    public function render(WithdrawalService $service, Settings $settings): View
    {
        $amount = max(0, (int) $this->amount);

        return view('livewire.withdraw-form', [
            'wallet' => auth()->user()->wallet()->first(),
            'bankAccounts' => auth()->user()->bankAccounts()->orderByDesc('is_default')->get(),
            'fee' => $amount > 0 ? $service->fee($amount) : 0,
            'min' => (int) setting('withdraw_min'),
            'methods' => $this->availableMethods(),
            'selectedMethod' => WithdrawalMethod::tryFrom($this->method) ?? WithdrawalMethod::Bank,
            'carriers' => PhoneCarrier::cases(),
            'phoneAmounts' => $settings->phoneWithdrawAmounts(),
        ]);
    }

    private function requestBankTransfer(WithdrawalService $service): ?Withdrawal
    {
        $this->validate([
            'bankAccountId' => ['required', 'integer'],
            'amount' => ['required', 'integer', 'min:1'],
        ], [
            'bankAccountId.required' => 'Hãy chọn tài khoản nhận tiền.',
            'amount.required' => 'Hãy nhập số tiền muốn rút.',
        ]);

        $bankAccount = auth()->user()->bankAccounts()->find($this->bankAccountId);

        if (! $bankAccount) {
            $this->addError('bankAccountId', 'Tài khoản nhận tiền không hợp lệ.');

            return null;
        }

        try {
            return $service->request(auth()->user(), $bankAccount, (int) $this->amount, request()->ip());
        } catch (BusinessRuleException $e) {
            $this->addError('amount', $e->getMessage());

            return null;
        }
    }

    private function requestPhonePayout(WithdrawalService $service, WithdrawalMethod $method): ?Withdrawal
    {
        $this->validate([
            'carrier' => ['required', Rule::enum(PhoneCarrier::class)],
            'amount' => ['required', 'integer', 'min:1'],
            'phoneNumber' => [Rule::requiredIf($method === WithdrawalMethod::PhoneTopup), 'nullable', 'string', 'max:20'],
        ], [
            'carrier.required' => 'Hãy chọn nhà mạng.',
            'amount.required' => 'Hãy chọn mệnh giá.',
            'phoneNumber.required' => 'Hãy nhập số điện thoại cần nạp.',
        ]);

        if ($method === WithdrawalMethod::PhoneTopup && WithdrawalService::normalizePhoneNumber((string) $this->phoneNumber) === null) {
            $this->addError('phoneNumber', 'Số điện thoại di động gồm 10 chữ số, bắt đầu bằng 03, 05, 07, 08 hoặc 09.');

            return null;
        }

        try {
            return $service->requestPhone(auth()->user(), $method, PhoneCarrier::from($this->carrier), (int) $this->amount, $this->phoneNumber, request()->ip());
        } catch (BusinessRuleException $e) {
            $this->addError('amount', $e->getMessage());

            return null;
        }
    }

    /**
     * @return list<WithdrawalMethod>
     */
    private function availableMethods(): array
    {
        $hasPhoneAmounts = app(Settings::class)->phoneWithdrawAmounts() !== [];

        return array_values(array_filter(
            WithdrawalMethod::cases(),
            fn (WithdrawalMethod $method) => $method->isEnabled() && (! $method->isPhone() || $hasPhoneAmounts),
        ));
    }
}
