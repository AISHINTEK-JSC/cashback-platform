@props(['title', 'description' => null])

<div {{ $attributes->class(['mb-3 flex items-end justify-between gap-4']) }}>
    <div class="min-w-0">
        <h2 class="font-semibold text-stone-900">{{ $title }}</h2>
        @if ($description)
            <p class="text-sm text-stone-500">{{ $description }}</p>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="shrink-0 text-sm">{{ $slot }}</div>
    @endif
</div>
