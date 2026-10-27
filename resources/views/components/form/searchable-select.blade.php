@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Chọn...',
    'searchPlaceholder' => 'Tìm kiếm...',
    'empty' => 'Không tìm thấy kết quả phù hợp.',
])

{{-- Ô chọn có tìm kiếm (gõ không dấu vẫn ra), option: value, label, description?, image? --}}
<div
    x-data="searchableSelect(@js(array_values($options)), @js(old($name, $value)))"
    @click.outside="open && close(false)"
    @keydown.escape.prevent.stop="open && close()"
    class="relative"
>
    @if ($label)
        <label for="{{ $name }}" class="label">{{ $label }}</label>
    @endif

    <input type="hidden" name="{{ $name }}" :value="selected ?? ''">

    <button
        type="button"
        id="{{ $name }}"
        x-ref="button"
        @click="toggle()"
        @keydown.arrow-down.prevent="show()"
        aria-haspopup="listbox"
        :aria-expanded="open"
        @class(['input flex min-h-11 items-center gap-3 text-left', 'border-red-400' => $errors->has($name)])
    >
        <template x-if="selectedOption">
            <span class="flex min-w-0 flex-1 items-center gap-2.5">
                <template x-if="selectedOption.image">
                    <span class="grid h-7 w-11 shrink-0 place-items-center overflow-hidden rounded-md bg-white px-0.5 ring-1 ring-stone-200">
                        <img :src="selectedOption.image" alt="" class="size-full object-contain">
                    </span>
                </template>
                <span class="truncate font-medium text-stone-900" x-text="selectedOption.label"></span>
            </span>
        </template>
        <span x-show="!selectedOption" class="flex-1 text-stone-400">{{ $placeholder }}</span>
        <x-heroicon-o-chevron-up-down class="size-5 shrink-0 text-stone-400" />
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition duration-100 ease-out"
        x-transition:enter-start="-translate-y-1 opacity-0"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-end="opacity-0"
        class="absolute inset-x-0 z-30 mt-2 overflow-hidden rounded-xl border border-stone-200 bg-surface shadow-lg"
    >
        <div class="border-b border-stone-200 p-2">
            <div class="relative">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-stone-400" />
                <input
                    type="text"
                    x-ref="search"
                    x-model="query"
                    @keydown.arrow-down.prevent="move(1)"
                    @keydown.arrow-up.prevent="move(-1)"
                    @keydown.enter.prevent="chooseActive()"
                    placeholder="{{ $searchPlaceholder }}"
                    autocomplete="off"
                    class="input !py-2 pl-9"
                >
            </div>
        </div>

        <ul x-ref="list" role="listbox" class="max-h-72 overflow-y-auto overscroll-contain p-1">
            <template x-for="(option, index) in filtered" :key="option.value">
                <li
                    role="option"
                    :aria-selected="option.value === selected"
                    :data-active="index === active"
                    @click="choose(option)"
                    @mousemove="active = index"
                    :class="index === active ? 'bg-stone-100' : ''"
                    class="flex cursor-pointer items-center gap-3 rounded-lg px-2.5 py-2"
                >
                    <template x-if="option.image">
                        <span class="grid h-9 w-14 shrink-0 place-items-center overflow-hidden rounded-lg bg-white px-1 ring-1 ring-stone-200">
                            <img :src="option.image" alt="" class="size-full object-contain" loading="lazy">
                        </span>
                    </template>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-stone-900" x-text="option.label"></span>
                        <span x-show="option.description" class="block truncate text-xs text-stone-500" x-text="option.description"></span>
                    </span>
                    <x-heroicon-s-check-circle x-show="option.value === selected" class="size-5 shrink-0 text-brand-600" />
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-8 text-center text-sm text-stone-500">{{ $empty }}</li>
        </ul>
    </div>

    @error($name)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
