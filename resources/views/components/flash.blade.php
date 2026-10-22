@if (session('success'))
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
        <x-heroicon-o-check-circle class="size-5 shrink-0" />
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <x-heroicon-o-exclamation-triangle class="size-5 shrink-0" />
        <span>{{ session('error') }}</span>
    </div>
@endif
