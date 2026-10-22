<?php extract((new \Illuminate\Support\Collection($attributes->getAttributes()))->mapWithKeys(function ($value, $key) { return [Illuminate\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>

<x-heroicon-o-queue-list  {{ $attributes }}>

{{ $slot ?? "" }}
</x-heroicon-o-queue-list>