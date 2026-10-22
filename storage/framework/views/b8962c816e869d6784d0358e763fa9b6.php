<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'value', 'hint' => null, 'tone' => 'default']));

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

foreach (array_filter((['label', 'value', 'hint' => null, 'tone' => 'default']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php echo e($attributes->class([
    'rounded-2xl border p-5',
    'border-brand-600 bg-brand-600 text-white' => $tone === 'brand',
    'border-stone-200 bg-white' => $tone !== 'brand',
])); ?>>
    <p class="<?php echo \Illuminate\Support\Arr::toCssClasses(['text-sm', 'text-brand-100' => $tone === 'brand', 'text-stone-500' => $tone !== 'brand']); ?>"><?php echo e($label); ?></p>
    <p class="mt-1 text-2xl font-bold tabular-nums"><?php echo e($value); ?></p>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hint): ?>
        <p class="<?php echo \Illuminate\Support\Arr::toCssClasses(['mt-1 text-xs', 'text-brand-100' => $tone === 'brand', 'text-stone-500' => $tone !== 'brand']); ?>"><?php echo e($hint); ?></p>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div>
<?php /**PATH /Users/thanhlv/Documents/Projects/cashback-platform/resources/views/components/stat-card.blade.php ENDPATH**/ ?>