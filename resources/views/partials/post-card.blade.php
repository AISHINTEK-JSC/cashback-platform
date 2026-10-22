<a href="{{ route('posts.show', $post) }}" class="card group flex flex-col overflow-hidden transition hover:shadow-md">
    @if ($post->thumbnail)
        <img src="{{ $post->thumbnailUrl() }}" alt="" class="aspect-[16/9] w-full object-cover" loading="lazy">
    @else
        <div class="grid aspect-[16/9] w-full place-items-center bg-gradient-to-br from-brand-100 to-brand-50 text-brand-400">
            <x-heroicon-o-receipt-percent class="size-12" />
        </div>
    @endif
    <div class="flex flex-1 flex-col p-5">
        @if ($post->category)
            <span class="text-xs font-semibold text-brand-700">{{ $post->category->name }}</span>
        @endif
        <h3 class="mt-1 line-clamp-2 font-semibold text-stone-900 group-hover:text-brand-700">{{ $post->title }}</h3>
        @if ($post->excerpt)
            <p class="mt-2 line-clamp-3 text-sm text-stone-600">{{ $post->excerpt }}</p>
        @endif
        <span class="mt-auto pt-4 text-xs text-stone-500">{{ $post->published_at?->format('d/m/Y') }}</span>
    </div>
</a>
