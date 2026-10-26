<x-layouts.app
    :title="$currentCategory ? $currentCategory->name.' - Blog' : 'Blog mẹo săn sale, hoàn tiền và kiếm tiền affiliate'"
    :description="$currentCategory
        ? 'Tổng hợp bài viết '.mb_strtolower($currentCategory->name).': kinh nghiệm thực tế, hướng dẫn từng bước, cập nhật thường xuyên.'
        : 'Mẹo săn sale, săn mã giảm giá, hướng dẫn nhận hoàn tiền khi mua sắm online và kinh nghiệm kiếm tiền affiliate cho người mới.'"
>
    <div class="container-page py-10">
        <h1 class="text-3xl font-bold text-stone-900">{{ $currentCategory?->name ?? 'Blog mẹo săn sale' }}</h1>

        @if ($categories->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('posts.index') }}" @class(['btn !py-2', 'btn-primary' => ! $currentCategory, 'btn-secondary' => $currentCategory])>Tất cả</a>
                @foreach ($categories as $category)
                    <a href="{{ route('posts.category', $category) }}" @class(['btn !py-2', 'btn-primary' => $currentCategory?->is($category), 'btn-secondary' => ! $currentCategory?->is($category)])>{{ $category->name }}</a>
                @endforeach
            </div>
        @endif

        @if ($posts->isEmpty())
            <x-empty-state class="mt-8" icon="document-text" title="Chưa có bài viết" />
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @endforeach
            </div>
            <div class="mt-8">{{ $posts->links() }}</div>
        @endif
    </div>
</x-layouts.app>
