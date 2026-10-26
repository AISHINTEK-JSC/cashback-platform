@props(['data'])

{{-- Dữ liệu có cấu trúc schema.org; JSON_HEX_TAG để nội dung không thể đóng thẻ script --}}
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', ...$data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
