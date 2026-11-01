@php
    $slug = $slug ?? '';
@endphp
<span class="cat-icon" aria-hidden="true">
@switch($slug)
    @case('photographer')
        <svg viewBox="0 0 24 24"><path d="M7 8h1.4l1.2-2h5.8l1.2 2H17a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2z"/><circle cx="12" cy="13" r="3"/></svg>
        @break
    @case('videographer')
        <svg viewBox="0 0 24 24"><rect x="3" y="7" width="12" height="10" rx="2"/><path d="m15 10 5-2.2v8.4L15 14"/></svg>
        @break
    @case('reels')
        <svg viewBox="0 0 24 24"><rect x="8" y="3" width="8" height="18" rx="2"/><path d="M11 6.5h2"/></svg>
        @break
    @case('model')
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M6 20c.8-3.6 3-5.3 6-5.3s5.2 1.7 6 5.3"/></svg>
        @break
    @case('studio')
        <svg viewBox="0 0 24 24"><path d="M4 11 12 4l8 7v9H4z"/><path d="M9 20v-7h6v7"/></svg>
        @break
    @case('ugc')
        <svg viewBox="0 0 24 24"><circle cx="9" cy="8.5" r="2.4"/><circle cx="16" cy="9.2" r="2"/><path d="M4.2 19c.7-2.8 2.5-4.3 4.8-4.3 1.2 0 2.3.4 3.2 1.1 1-.8 2.2-1.2 3.6-1.2 2 0 3.6 1.1 4.4 3.4"/></svg>
        @break
    @case('food_stylist')
        <svg viewBox="0 0 24 24"><path d="M8 4v7M6.2 4v6.2A1.8 1.8 0 0 0 10 10.2V4M16 4v16M16 4h2.4a2.4 2.4 0 0 1 0 4.8H16"/></svg>
        @break
    @case('ai_motion')
        <svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="3"/><path d="m10 9 6 3-6 3z"/></svg>
        @break
    @case('voice')
        <svg viewBox="0 0 24 24"><rect x="9" y="3" width="6" height="10" rx="3"/><path d="M7 12a5 5 0 0 0 10 0M12 17v3M9.5 20h5"/></svg>
        @break
    @default
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="7"/></svg>
@endswitch
</span>
