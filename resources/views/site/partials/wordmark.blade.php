@php
    $size = $size ?? 'md';
@endphp
<a class="wordmark wordmark-{{ $size }}" href="{{ route('site.home') }}" aria-label="Lens home">
    <img
        class="wordmark-logo"
        src="{{ asset('branding/lenslogo_clear.png') }}"
        alt="Lens"
        width="180"
        height="56"
    >
</a>
