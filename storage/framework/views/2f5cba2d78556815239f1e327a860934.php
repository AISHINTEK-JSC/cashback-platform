<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => ['title' => 'Điều khoản sử dụng']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Điều khoản sử dụng']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="container-page max-w-3xl py-10">
        <h1 class="text-3xl font-bold text-stone-900">Điều khoản sử dụng</h1>
        <p class="mt-2 text-sm text-stone-500">Bản mẫu, cần được bộ phận pháp lý rà soát trước khi vận hành chính thức.</p>
        <div class="prose-content mt-6">
            <h2>1. Tài khoản</h2>
            <p>Mỗi cá nhân chỉ được sở hữu một tài khoản. Thông tin đăng ký và tài khoản nhận tiền phải chính chủ.</p>
            <h2>2. Hoàn tiền</h2>
            <p>Số tiền hoàn phụ thuộc hoa hồng thực tế sàn trả cho đơn hàng hợp lệ. Chúng tôi không đảm bảo mọi đơn đều được sàn ghi nhận.</p>
            <h2>3. Hành vi bị cấm</h2>
            <ul>
                <li>Tạo nhiều tài khoản, tự giới thiệu chính mình, dùng chung tài khoản ngân hàng giữa nhiều tài khoản.</li>
                <li>Đặt đơn ảo, đặt rồi huỷ hàng loạt, hoặc bất kỳ hình thức gian lận hoa hồng nào.</li>
            </ul>
            <p>Tài khoản vi phạm có thể bị khoá, thu hồi số dư và từ chối các yêu cầu rút tiền.</p>
            <h2>4. Rút tiền</h2>
            <p>Yêu cầu rút được xử lý trong 1-3 ngày làm việc. Chúng tôi có thể yêu cầu xác minh danh tính trước khi chi trả.</p>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $attributes = $__attributesOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__attributesOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $component = $__componentOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__componentOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php /**PATH /Users/thanhlv/Documents/Projects/cashback-platform/resources/views/pages/terms.blade.php ENDPATH**/ ?>