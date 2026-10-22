<div class="card flex flex-col p-5">
    <div class="flex items-start justify-between gap-3">
        <?php if (isset($component)) { $__componentOriginal6916a79b9f888417cb7a7059f2dd60f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6916a79b9f888417cb7a7059f2dd60f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.platform-badge','data' => ['platform' => $voucher->platform]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('platform-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['platform' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($voucher->platform)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6916a79b9f888417cb7a7059f2dd60f2)): ?>
<?php $attributes = $__attributesOriginal6916a79b9f888417cb7a7059f2dd60f2; ?>
<?php unset($__attributesOriginal6916a79b9f888417cb7a7059f2dd60f2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6916a79b9f888417cb7a7059f2dd60f2)): ?>
<?php $component = $__componentOriginal6916a79b9f888417cb7a7059f2dd60f2; ?>
<?php unset($__componentOriginal6916a79b9f888417cb7a7059f2dd60f2); ?>
<?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucher->expires_at): ?>
            <span class="text-xs text-stone-500">HSD <?php echo e($voucher->expires_at->format('d/m H:i')); ?></span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
    <p class="mt-3 text-lg font-bold text-brand-700"><?php echo e($voucher->discountLabel()); ?></p>
    <p class="mt-1 text-sm text-stone-700"><?php echo e($voucher->title); ?></p>
    <p class="mt-1 text-xs text-stone-500">
        Đơn tối thiểu <?php echo e(vnd($voucher->min_order)); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucher->max_discount): ?>, giảm tối đa <?php echo e(vnd($voucher->max_discount)); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </p>
    <div class="mt-4 flex items-center gap-2 pt-1">
        <span class="flex-1 rounded-lg border border-dashed border-brand-300 bg-brand-50 px-3 py-2 text-center font-mono text-sm font-semibold tracking-wider text-brand-800"><?php echo e($voucher->code); ?></span>
        <?php if (isset($component)) { $__componentOriginalfdd551b0954226e5f576f90ec6c69f1e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfdd551b0954226e5f576f90ec6c69f1e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.copy-button','data' => ['text' => $voucher->code,'label' => 'Lưu mã']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('copy-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($voucher->code),'label' => 'Lưu mã']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfdd551b0954226e5f576f90ec6c69f1e)): ?>
<?php $attributes = $__attributesOriginalfdd551b0954226e5f576f90ec6c69f1e; ?>
<?php unset($__attributesOriginalfdd551b0954226e5f576f90ec6c69f1e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfdd551b0954226e5f576f90ec6c69f1e)): ?>
<?php $component = $__componentOriginalfdd551b0954226e5f576f90ec6c69f1e; ?>
<?php unset($__componentOriginalfdd551b0954226e5f576f90ec6c69f1e); ?>
<?php endif; ?>
    </div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucher->url): ?>
        <a href="<?php echo e($voucher->url); ?>" target="_blank" rel="noopener nofollow" class="mt-2 text-center text-xs font-medium text-stone-600 hover:text-stone-900">Đến trang lấy mã</a>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/thanhlv/Documents/Projects/cashback-platform/resources/views/partials/voucher-card.blade.php ENDPATH**/ ?>