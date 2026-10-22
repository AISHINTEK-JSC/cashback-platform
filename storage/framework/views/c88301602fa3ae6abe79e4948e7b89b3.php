<?php
    $faqs = [
        'Hoàn tiền là gì, tiền từ đâu ra?' => 'Các sàn TMĐT trả hoa hồng cho đối tác tiếp thị liên kết khi có người mua qua link của đối tác. Chúng tôi là đối tác đó và chia lại phần lớn hoa hồng cho bạn.',
        'Mua qua link hoàn tiền có đắt hơn không?' => 'Không. Giá sản phẩm, mã giảm giá và quyền lợi của bạn trên sàn không thay đổi.',
        'Bao lâu thì đơn được ghi nhận?' => 'Thường từ vài phút tới vài giờ sau khi đặt, tuỳ tốc độ cập nhật báo cáo của sàn.',
        'Bao lâu thì rút được tiền?' => 'Sau khi sàn chốt hoa hồng, thường 30-60 ngày kể từ ngày mua tuỳ sàn. Thời gian dự kiến được hiển thị ở từng đơn.',
        'Vì sao số tiền hoàn khác dự kiến?' => 'Hoa hồng thực tế do sàn quyết định theo từng sản phẩm, phí vận chuyển, mã giảm giá và chương trình khuyến mãi tại thời điểm mua.',
        'Rút tiền tối thiểu bao nhiêu?' => 'Tối thiểu '.vnd((int) setting('withdraw_min')).' mỗi lần. Phí rút: '.vnd((int) setting('withdraw_fee')).'.',
        'Giới thiệu bạn bè nhận gì?' => 'Bạn nhận hoa hồng F1 trên mỗi đơn của người bạn mời trực tiếp và hoa hồng F2 trên đơn của người họ mời, xem tỷ lệ ở trang Tỷ lệ hoàn tiền.',
        'Đơn bị huỷ thì sao?' => 'Đơn bị huỷ hoặc hoàn hàng sẽ không được hoàn tiền. Nếu tiền đã vào số dư khả dụng, hệ thống sẽ thu hồi lại.',
    ];
?>

<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => ['title' => 'Câu hỏi thường gặp']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Câu hỏi thường gặp']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div class="container-page max-w-3xl py-10">
        <h1 class="text-3xl font-bold text-stone-900">Câu hỏi thường gặp</h1>
        <div class="mt-8 space-y-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $faqs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $question => $answer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <details class="card group p-5">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-stone-900">
                        <?php echo e($question); ?>

                        <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-chevron-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'size-5 shrink-0 text-stone-400 transition group-open:rotate-90']); ?>
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
                    </summary>
                    <p class="mt-3 text-stone-600"><?php echo e($answer); ?></p>
                </details>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
        <p class="mt-8 text-stone-600">Chưa có câu trả lời? <a href="<?php echo e(route('dashboard.tickets.create')); ?>" class="font-semibold text-brand-700 underline">Gửi yêu cầu hỗ trợ</a>.</p>
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
<?php /**PATH /Users/thanhlv/Documents/Projects/cashback-platform/resources/views/pages/faq.blade.php ENDPATH**/ ?>