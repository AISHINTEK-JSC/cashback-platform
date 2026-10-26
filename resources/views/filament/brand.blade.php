{{-- Logo trang quản trị; Filament dùng CSS riêng nên không có class Tailwind của site --}}
<span style="display: flex; align-items: center; gap: 0.6rem; font-size: 1.05rem; font-weight: 800; letter-spacing: -0.01em">
    <x-logo-mark style="width: 2rem; height: 2rem; flex-shrink: 0" />
    <span>{{ setting('site_name') }}</span>
</span>
