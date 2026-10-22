<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => ['title' => 'Chính sách bảo mật']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Chính sách bảo mật']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="container-page max-w-3xl py-10">
        <h1 class="text-3xl font-bold text-stone-900">Chính sách bảo mật</h1>
        <p class="mt-2 text-sm text-stone-500">Bản mẫu, cần được bộ phận pháp lý rà soát trước khi vận hành chính thức.</p>
        <div class="prose-content mt-6">
            <h2>Dữ liệu chúng tôi thu thập</h2>
            <ul>
                <li>Thông tin tài khoản: họ tên, email, số điện thoại.</li>
                <li>Thông tin nhận tiền: ngân hàng, số tài khoản, tên chủ tài khoản.</li>
                <li>Dữ liệu đơn hàng do sàn cung cấp qua chương trình tiếp thị liên kết.</li>
                <li>Nhật ký đăng nhập, địa chỉ IP, định danh thiết bị để phòng chống gian lận.</li>
            </ul>
            <h2>Mục đích sử dụng</h2>
            <p>Ghi nhận đơn hàng, tính và chi trả tiền hoàn, hỗ trợ khiếu nại và phòng chống gian lận. Chúng tôi không bán dữ liệu cá nhân cho bên thứ ba.</p>
            <h2>Quyền của bạn</h2>
            <p>Bạn có thể yêu cầu xem, chỉnh sửa hoặc xoá dữ liệu cá nhân bằng cách gửi yêu cầu hỗ trợ.</p>
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
<?php /**PATH /Users/thanhlv/Documents/Projects/cashback-platform/resources/views/pages/privacy.blade.php ENDPATH**/ ?>