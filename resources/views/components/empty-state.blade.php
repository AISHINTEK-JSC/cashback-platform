@props(['icon' => 'inbox', 'title', 'action' => null, 'actionUrl' => null])

<div {{ $attributes->class(['card flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="relative grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-600 ring-8 ring-brand-50/50">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-7" />
    </span>
    <p class="mt-5 font-semibold text-stone-900">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <p class="mt-1 max-w-sm text-sm text-stone-500">{{ $slot }}</p>
    @endif
    @if ($action)
        <a href="{{ $actionUrl }}" class="btn btn-primary mt-5">{{ $action }}</a>
    @endif
</div>
