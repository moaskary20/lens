@php
    $slug = $type['slug'] ?? '';
    $label = $type['label'] ?? ($type['name'] ?? '');
    $href = $href ?? $type['href'] ?? route('site.search', ['vendor_type' => $slug]);
    $local = $slug !== '' ? public_path('branding/categories/'.$slug.'.jpg') : null;
    $bg = ($local && is_file($local))
        ? '/branding/categories/'.$slug.'.jpg'
        : '/branding/categories/photographer.jpg';
@endphp
<a class="cat" href="{{ $href }}" data-cat-card>
    <span class="cat-media" style="background-image: url('{{ $bg }}')" aria-hidden="true"></span>
    <span class="cat-copy">
        @include('site.partials.category-icon', ['slug' => $slug])
        <span class="cat-label">
            <span class="cat-label-text">{{ $label }}</span>
            <svg class="cat-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
        </span>
    </span>
</a>
