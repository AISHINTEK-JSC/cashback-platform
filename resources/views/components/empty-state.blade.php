@props(['icon' => 'inbox', 'title', 'action' => null, 'actionUrl' => null])

<div {{ $attributes->class(['card flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-full bg-stone-100 text-stone-500">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="size-6" />
    </span>
    <p class="mt-4 font-semibold text-stone-900">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <p class="mt-1 max-w-sm text-sm text-stone-600">{{ $slot }}</p>
    @endif
    @if ($action)
        <a href="{{ $actionUrl }}" class="btn btn-primary mt-5">{{ $action }}</a>
    @endif
</div>
