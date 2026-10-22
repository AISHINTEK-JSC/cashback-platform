<div class="card p-5 sm:p-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-sm text-stone-500">Số dư khả dụng</p>
            <p class="text-2xl font-bold text-stone-900 tabular-nums"><?php echo e(vnd($wallet->available_balance)); ?></p>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($wallet->frozen_balance > 0): ?>
            <div class="text-right">
                <p class="text-sm text-stone-500">Đang chờ rút</p>
                <p class="font-semibold text-stone-700 tabular-nums"><?php echo e(vnd($wallet->frozen_balance)); ?></p>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bankAccounts->isEmpty()): ?>
        <div class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">
            Bạn chưa có tài khoản nhận tiền.
            <a href="<?php echo e(route('dashboard.bank-accounts.index')); ?>" class="font-semibold underline">Thêm tài khoản ngân hàng</a> để rút tiền.
        </div>
    <?php else: ?>
        <form wire:submit="submit" class="mt-5 space-y-4">
            <div>
                <label class="label" for="bankAccountId">Nhận tiền về</label>
                <select id="bankAccountId" wire:model="bankAccountId" class="input">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $bankAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($account->id); ?>" <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'bank-'.e($account->id).''; ?>wire:key="bank-<?php echo e($account->id); ?>"><?php echo e($account->bank_name); ?> - <?php echo e($account->maskedNumber()); ?> - <?php echo e($account->account_name); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['bankAccountId'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="field-error"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div>
                <label class="label" for="amount">Số tiền muốn rút</label>
                <div class="flex gap-2">
                    <input id="amount" type="number" inputmode="numeric" min="0" step="1000" wire:model.live.debounce.400ms="amount" class="input" placeholder="Tối thiểu <?php echo e(vnd($min)); ?>">
                    <button type="button" wire:click="withdrawAll" class="btn btn-secondary shrink-0">Rút hết</button>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="field-error"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($amount): ?>
                <dl class="space-y-1 rounded-xl bg-stone-50 p-4 text-sm">
                    <div class="flex justify-between"><dt class="text-stone-600">Phí rút</dt><dd class="tabular-nums"><?php echo e(vnd($fee)); ?></dd></div>
                    <div class="flex justify-between font-semibold"><dt>Thực nhận</dt><dd class="tabular-nums text-emerald-700"><?php echo e(vnd(max(0, (int) $amount - $fee))); ?></dd></div>
                </dl>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <button type="submit" class="btn btn-primary w-full" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Tạo yêu cầu rút tiền</span>
                <span wire:loading wire:target="submit">Đang xử lý...</span>
            </button>
        </form>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/thanhlv/Documents/Projects/cashback-platform/resources/views/livewire/withdraw-form.blade.php ENDPATH**/ ?>