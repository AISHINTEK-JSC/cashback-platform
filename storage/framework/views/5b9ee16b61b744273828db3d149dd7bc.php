<?php if (isset($component)) { $__componentOriginalc3ffbec469e53ba0c225d6242a0ccc8d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc3ffbec469e53ba0c225d6242a0ccc8d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.auth-card','data' => ['title' => 'Quên mật khẩu','subtitle' => 'Nhập email đã đăng ký, chúng tôi sẽ gửi link đặt lại mật khẩu.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Quên mật khẩu','subtitle' => 'Nhập email đã đăng ký, chúng tôi sẽ gửi link đặt lại mật khẩu.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <form method="POST" action="<?php echo e(route('password.email')); ?>" class="space-y-4">
        <?php echo csrf_field(); ?>
        <?php if (isset($component)) { $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'email','type' => 'email','label' => 'Email','autocomplete' => 'email','required' => true,'autofocus' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'email','type' => 'email','label' => 'Email','autocomplete' => 'email','required' => true,'autofocus' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b)): ?>
<?php $attributes = $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b; ?>
<?php unset($__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c2a97ab476b69c1189ee85d1a95204b)): ?>
<?php $component = $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b; ?>
<?php unset($__componentOriginal5c2a97ab476b69c1189ee85d1a95204b); ?>
<?php endif; ?>
        <button type="submit" class="btn btn-primary w-full">Gửi link đặt lại</button>
    </form>
     <?php $__env->slot('footer', null, []); ?> 
        <p class="mt-6 text-center text-sm text-stone-600"><a href="<?php echo e(route('login')); ?>" class="font-semibold text-brand-700 hover:underline">Quay lại đăng nhập</a></p>
     <?php $__env->endSlot(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc3ffbec469e53ba0c225d6242a0ccc8d)): ?>
<?php $attributes = $__attributesOriginalc3ffbec469e53ba0c225d6242a0ccc8d; ?>
<?php unset($__attributesOriginalc3ffbec469e53ba0c225d6242a0ccc8d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc3ffbec469e53ba0c225d6242a0ccc8d)): ?>
<?php $component = $__componentOriginalc3ffbec469e53ba0c225d6242a0ccc8d; ?>
<?php unset($__componentOriginalc3ffbec469e53ba0c225d6242a0ccc8d); ?>
<?php endif; ?>
<?php /**PATH /Users/thanhlv/Documents/Projects/cashback-platform/resources/views/auth/forgot-password.blade.php ENDPATH**/ ?>