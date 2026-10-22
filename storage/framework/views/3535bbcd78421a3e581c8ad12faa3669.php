<div>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($link): ?>
        <div class="rounded-2xl border border-emerald-200 bg-white p-4 text-left shadow-sm sm:p-5">
            <div class="flex items-start gap-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($link->product_image): ?>
                    <img src="<?php echo e($link->product_image); ?>" alt="" class="size-20 shrink-0 rounded-xl object-cover">
                <?php else: ?>
                    <span class="grid size-20 shrink-0 place-items-center rounded-xl bg-stone-100 text-stone-400">
                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-shopping-bag'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-8']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="min-w-0 flex-1">
                    <?php if (isset($component)) { $__componentOriginal6916a79b9f888417cb7a7059f2dd60f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6916a79b9f888417cb7a7059f2dd60f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.platform-badge','data' => ['platform' => $link->platform]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('platform-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['platform' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($link->platform)]); ?>
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
                    <p class="mt-1 line-clamp-2 font-semibold text-stone-900"><?php echo e($link->product_name ?? 'Sản phẩm của bạn'); ?></p>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 text-sm">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($link->price): ?>
                            <span class="text-stone-600"><?php echo e(vnd($link->price)); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <span class="font-semibold text-emerald-700">
                            <?php echo e($link->estimated_rate ? 'Hoàn dự kiến ~'.rtrim(rtrim(number_format((float) $link->estimated_rate, 2, ',', '.'), '0'), ',').'%' : 'Có hoàn tiền'); ?>

                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2 rounded-xl bg-stone-100 p-2 pl-3">
                <span class="min-w-0 flex-1 truncate font-mono text-sm text-stone-700"><?php echo e($link->redirectUrl()); ?></span>
                <?php if (isset($component)) { $__componentOriginalfdd551b0954226e5f576f90ec6c69f1e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfdd551b0954226e5f576f90ec6c69f1e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.copy-button','data' => ['text' => $link->redirectUrl(),'class' => '!py-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('copy-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['text' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($link->redirectUrl()),'class' => '!py-2']); ?>
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

            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <a href="<?php echo e($link->redirectUrl()); ?>" target="_blank" rel="noopener" class="btn btn-primary">
                    Mua ngay trên <?php echo e($link->platform->name); ?>

                    <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-top-right-on-square'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                </a>
                <button type="button" wire:click="resetForm" class="btn btn-secondary">Lấy link khác</button>
            </div>

            <p class="mt-3 flex items-start gap-2 text-xs text-stone-500">
                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-exclamation-triangle'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-4 shrink-0 text-amber-500']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                Mở link rồi mua ngay trong phiên đó. Giỏ hàng nên trống trước khi thêm sản phẩm, không bấm link tiếp thị khác trước khi đặt đơn.
            </p>
        </div>
    <?php else: ?>
        <form wire:submit="generate" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['flex gap-2', 'flex-col sm:flex-row' => ! $compact]); ?>">
            <div class="relative flex-1">
                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-stone-400']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                <input
                    type="url"
                    wire:model="url"
                    placeholder="Dán link sản phẩm Shopee, TikTok Shop, Lazada..."
                    class="input !rounded-2xl !py-3.5 !pr-24 !pl-12 !text-base"
                    autocomplete="off"
                    inputmode="url"
                >
                <button
                    type="button"
                    x-data
                    x-show="navigator.clipboard && navigator.clipboard.readText"
                    @click="navigator.clipboard.readText().then(t => $wire.set('url', t.trim()))"
                    class="absolute top-1/2 right-2 -translate-y-1/2 rounded-lg bg-stone-100 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-200"
                >Dán</button>
            </div>
            <button type="submit" class="btn btn-primary !rounded-2xl !px-6 !py-3.5 !text-base" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="generate">Lấy link hoàn tiền</span>
                <span wire:loading wire:target="generate">Đang tạo link...</span>
            </button>
        </form>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="field-error text-left"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/thanhlv/Documents/Projects/cashback-platform/resources/views/livewire/link-generator.blade.php ENDPATH**/ ?>