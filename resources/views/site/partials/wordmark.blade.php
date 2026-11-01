@php
    $size = $size ?? 'md';
@endphp
<a class="wordmark wordmark-{{ $size }}" href="{{ route('site.home') }}">
    <svg class="aperture" viewBox="0 0 24 24" aria-hidden="true">
        <circle cx="12" cy="12" r="9.2"></circle>
        <path d="M14.2 7.2 19.6 16.2"></path>
        <path d="M9.8 7.2h9.6"></path>
        <path d="M7.6 12 13.2 2.8"></path>
        <path d="M9.8 16.8 4.4 7.8"></path>
        <path d="M14.2 16.8H4.6"></path>
        <path d="M16.4 12 10.8 21.2"></path>
    </svg>
    <span>
        <strong>Lens</strong>
        <small>Find. Book. Create.</small>
    </span>
</a>
