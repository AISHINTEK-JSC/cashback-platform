<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['platform']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['platform']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $classes = match ($platform->code) {
        'shopee' => 'bg-[#ee4d2d] text-white',
        'tiktok' => 'bg-stone-900 text-white',
        'lazada' => 'bg-[#0f136d] text-white',
        default => 'bg-stone-200 text-stone-800',
    };
?>

<span <?php echo e($attributes->class(['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold whitespace-nowrap', $classes])); ?>><?php echo e($platform->name); ?></span>
<?php /**PATH /Users/thanhlv/Documents/Projects/cashback-platform/resources/views/components/platform-badge.blade.php ENDPATH**/ ?>