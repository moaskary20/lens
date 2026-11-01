@php
    $slug = $type['slug'] ?? '';
    $label = $type['label'] ?? '';
    $href = $href ?? $type['href'] ?? route('site.search', ['vendor_type' => $slug]);
@endphp
<a class="cat" href="{{ $href }}" style="background-image: url('/branding/categories/{{ $slug }}.jpg')">
    <span class="cat-copy">
        @include('site.partials.category-icon', ['slug' => $slug])
        <span class="cat-label">
            {{ $label }}
            <svg class="cat-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        </span>
    </span>
</a>
