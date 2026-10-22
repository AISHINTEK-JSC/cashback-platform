@props(['title', 'subtitle' => null])

<x-layouts.app :title="$title">
    <div class="container-page flex justify-center py-12">
        <div class="w-full max-w-md">
            <div class="card p-6 sm:p-8">
                <h1 class="text-2xl font-bold text-stone-900">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-1 text-sm text-stone-600">{{ $subtitle }}</p>
                @endif
                <div class="mt-6">
                    <x-flash />
                    {{ $slot }}
                </div>
            </div>
            {{ $footer ?? '' }}
        </div>
    </div>
</x-layouts.app>
