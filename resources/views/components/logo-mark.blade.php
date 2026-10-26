{{-- Logo: ví nhận đồng xu, cùng thiết kế với public/favicon.svg --}}
@php($id = 'logo-'.Str::random(6))

<svg {{ $attributes }} viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <linearGradient id="{{ $id }}-bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#ff6a2b" />
            <stop offset="1" stop-color="#c2380f" />
        </linearGradient>
        <linearGradient id="{{ $id }}-coin" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffd65c" />
            <stop offset="1" stop-color="#f5a623" />
        </linearGradient>
    </defs>
    <rect width="64" height="64" rx="15" fill="url(#{{ $id }}-bg)" />
    <circle cx="39" cy="17.5" r="10.5" fill="url(#{{ $id }}-coin)" />
    <path d="M39 12.2v8.3m0 0-3.3-3.3m3.3 3.3 3.3-3.3" fill="none" stroke="#b5460e" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M10 31a7 7 0 0 1 7-7h30a7 7 0 0 1 7 7v16a7 7 0 0 1-7 7H17a7 7 0 0 1-7-7Z" fill="#fff" />
    <path d="M40 34h14v12H40a6 6 0 0 1 0-12Z" fill="#ffe2d1" />
    <circle cx="41.5" cy="40" r="2.6" fill="#e04412" />
</svg>
