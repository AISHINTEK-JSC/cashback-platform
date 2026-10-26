<x-layouts.app :title="$post->meta_title ?: $post->title" :description="$post->meta_description ?: $post->excerpt" :image="$post->thumbnailUrl()" type="article" :noindex="$isPreview">
    <x-slot:head>
        @if ($post->published_at)
            <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}">
        @endif
        <x-json-ld :data="[
            '@graph' => [
                [
                    '@type' => 'BlogPosting',
                    'headline' => $post->title,
                    'description' => $post->meta_description ?: $post->excerpt,
                    'image' => url($post->thumbnailUrl() ?: asset('images/og-default.png')),
                    'datePublished' => $post->published_at?->toIso8601String(),
                    'dateModified' => $post->updated_at?->toIso8601String(),
                    'author' => ['@type' => 'Person', 'name' => $post->author->name],
                    'publisher' => ['@type' => 'Organization', 'name' => setting('site_name'), 'logo' => ['@type' => 'ImageObject', 'url' => asset('icon-512.png')]],
                    'mainEntityOfPage' => route('posts.show', $post),
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => array_values(array_filter([
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Blog', 'item' => route('posts.index')],
                        $post->category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $post->category->name, 'item' => route('posts.category', $post->category)] : null,
                        ['@type' => 'ListItem', 'position' => $post->category ? 3 : 2, 'name' => $post->title],
                    ])),
                ],
            ],
        ]" />
    </x-slot:head>

    @if ($isPreview)
        <div class="border-b border-amber-200 bg-amber-50 py-2.5 text-center text-sm font-medium text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
            Đang xem trước: {{ $post->isScheduled() ? 'bài hẹn giờ đăng lúc '.$post->published_at->format('H:i d/m/Y') : 'bản nháp' }}, chỉ quản trị viên nhìn thấy.
        </div>
    @endif

    <article class="container-page max-w-3xl py-10">
        <nav class="text-sm text-stone-500">
            <a href="{{ route('posts.index') }}" class="hover:text-stone-900">Blog</a>
            @if ($post->category)
                <span class="mx-1">/</span>
                <a href="{{ route('posts.category', $post->category) }}" class="hover:text-stone-900">{{ $post->category->name }}</a>
            @endif
        </nav>
        <h1 class="mt-3 text-3xl font-bold text-balance text-stone-900 sm:text-4xl">{{ $post->title }}</h1>
        <p class="mt-3 text-sm text-stone-500">{{ $post->published_at?->format('d/m/Y') }} · {{ $post->author->name }} · {{ number_format($post->view_count, 0, ',', '.') }} lượt xem</p>

        @if ($post->thumbnail)
            <img src="{{ $post->thumbnailUrl() }}" alt="{{ $post->title }}" class="mt-6 w-full rounded-2xl">
        @endif

        {{-- Nội dung do admin soạn trong trình soạn thảo của trang quản trị --}}
        <div class="prose-content mt-8">{!! $post->content !!}</div>

        <div class="card mt-10 p-6 text-center">
            <p class="font-semibold text-stone-900">Nhận hoàn tiền cho đơn hàng tiếp theo</p>
            <div class="mx-auto mt-4 max-w-xl">
                <livewire:link-generator />
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="container-page pb-10">
            <h2 class="text-xl font-bold text-stone-900">Bài viết liên quan</h2>
            <div class="mt-4 grid gap-6 md:grid-cols-3">
                @foreach ($related as $item)
                    @include('partials.post-card', ['post' => $item])
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.app>
