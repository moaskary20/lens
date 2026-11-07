@extends('site.layout', ['title' => $editing ? 'Vendor studio' : 'Join as a vendor', 'active' => 'account'])

@section('content')
@php
    $p = $profile ?? [];
    $vendor = $p['vendor'] ?? $p;
    $steps = [
        ['label' => 'Identity', 'hint' => 'Who you are'],
        ['label' => 'Type & bio', 'hint' => 'Your craft'],
        ['label' => 'Previous projects', 'hint' => 'Your work'],
        ['label' => 'Pricing', 'hint' => 'Your rates'],
        ['label' => 'Availability', 'hint' => 'Working hours'],
        ['label' => 'Payouts', 'hint' => 'Get paid'],
    ];
@endphp
<section class="acc-sub wrap vendor-join" data-reveal x-data="vendorWizard({ step: 1, total: 6 })">
    <header class="acc-sub-hero">
        <div class="acc-sub-hero-copy">
            @if($editing)
                <a class="acc-sub-back" href="{{ route('site.account') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                    Account
                </a>
            @else
                <a class="acc-sub-back" href="{{ route('site.join') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                    Choose role
                </a>
            @endif
            <div class="acc-sub-title-row">
                <span class="acc-sub-ico" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="12" cy="12" r="3.2"/><path d="M8 5V3m8 2V3"/></svg>
                </span>
                <div>
                    <h1>{{ $editing ? 'Edit vendor profile' : 'Become a Lens vendor' }}</h1>
                    <p>{{ $editing ? 'Update your studio details — same profile as the app.' : 'Six steps to publish on Lens — same verification flow as the mobile app.' }}</p>
                </div>
            </div>
            <ul class="acc-sub-tips">
                <li>Escrow-backed bookings</li>
                <li>Egypt-wide discovery</li>
                <li>Same login on web &amp; app</li>
            </ul>
        </div>
        <figure class="acc-sub-hero-art" aria-hidden="true">
            <img src="{{ asset('branding/account/join-vendor.svg') }}" alt="" loading="eager" decoding="async">
        </figure>
    </header>

    <div class="acc-sub-body">
        <nav class="vj-steps" aria-label="Onboarding steps">
            @foreach($steps as $i => $step)
                <button
                    type="button"
                    class="vj-step"
                    :class="{ 'is-active': step === {{ $i + 1 }}, 'is-done': step > {{ $i + 1 }} }"
                    @click="step = {{ $i + 1 }}"
                >
                    <span class="vj-step-num" aria-hidden="true">
                        <span x-show="step <= {{ $i + 1 }}">{{ $i + 1 }}</span>
                        <svg x-cloak x-show="step > {{ $i + 1 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m5 12 5 5L20 7"/></svg>
                    </span>
                    <span class="vj-step-copy">
                        <strong>{{ $step['label'] }}</strong>
                        <small>{{ $step['hint'] }}</small>
                    </span>
                </button>
            @endforeach
        </nav>

        <form method="post"
              action="{{ $editing ? route('site.vendors.profile.update') : route('site.vendors.join.submit') }}"
              enctype="multipart/form-data"
              class="acc-sub-card vj-card acc-sub-form"
              @submit="document.querySelectorAll('[data-vj-project]').forEach((card) => {
                  const title = (card.querySelector('[data-vj-title]')?.value || '').trim();
                  if (!title) {
                      card.querySelectorAll('input, select, textarea').forEach((el) => { el.disabled = true; });
                  }
              })">
            @csrf
            @unless($editing)
                <input type="hidden" name="role" value="vendor">
            @endunless

            <div x-show="step === 1" x-cloak class="vj-pane">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 19c1.8-3.4 4-5 6.5-5s4.7 1.6 6.5 5"/></svg>
                    </span>
                    <div>
                        <h2>Identity</h2>
                        <p>Legal name, contact, and verification photos.</p>
                    </div>
                </header>
                <div class="acc-sub-grid">
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="3.2"/><path d="M5.5 19c1.8-3.4 4-5 6.5-5s4.7 1.6 6.5 5"/></svg> Full name</span><input name="name" value="{{ old('name', $p['name'] ?? '') }}" @required(!$editing)></label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg> Email</span><input type="email" name="email" value="{{ old('email', $p['email'] ?? '') }}" @required(!$editing) @disabled($editing)></label>
                    @unless($editing)
                        <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V8a4 4 0 0 1 8 0v2"/></svg> Password</span><input type="password" name="password" required></label>
                    @endunless
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6.5 4.5h3l1.5 4-2 1.2a12 12 0 0 0 5.3 5.3l1.2-2 4 1.5v3A2 2 0 0 1 17.5 19.5 13 13 0 0 1 4.5 6.5a2 2 0 0 1 2-2z"/></svg> Phone</span><input name="phone" value="{{ old('phone', $p['phone'] ?? '') }}" required placeholder="01xxxxxxxxx" inputmode="numeric"></label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg> Date of birth</span><input type="date" name="date_of_birth" value="{{ old('date_of_birth', $vendor['date_of_birth'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg> Governorate</span>
                        <select name="city_id" required>
                            <option value="">Choose…</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id', $p['city_id'] ?? $vendor['city_id'] ?? null) == $city->id)>{{ $city->name_en }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"/><circle cx="12" cy="12" r="3"/></svg> Profile photo</span><input type="file" name="profile_photo" accept="image/*"></label>
                    <label><span class="acc-sub-label"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M7 15h10M9 9h.01M15 9h.01"/></svg> National ID image</span><input type="file" name="national_id_image" accept="image/*"></label>
                </div>
                <div class="vj-actions">
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div x-show="step === 2" x-cloak class="vj-pane">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h10M4 17h14"/></svg>
                    </span>
                    <div>
                        <h2>Type &amp; bio</h2>
                        <p>How clients discover you on Lens.</p>
                    </div>
                </header>
                <div class="acc-sub-grid">
                    <label><span class="acc-sub-label">Vendor type</span>
                        <select name="vendor_type" required>
                            @foreach($vendorTypes as $type)
                                <option value="{{ $type->slug }}" @selected(old('vendor_type', $vendor['vendor_type'] ?? $vendor['type'] ?? '') === $type->slug)>{{ $type->name_en }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label><span class="acc-sub-label">Display name</span><input name="display_name" value="{{ old('display_name', $vendor['display_name'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Profession</span><input name="profession" value="{{ old('profession', $vendor['profession'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Years experience</span><input type="number" min="0" name="years_experience" value="{{ old('years_experience', $vendor['years_experience'] ?? 1) }}" required></label>
                    <label class="acc-sub-span-2"><span class="acc-sub-label">Bio</span><textarea name="bio" required>{{ old('bio', $vendor['bio'] ?? '') }}</textarea></label>
                    <label><span class="acc-sub-label">WhatsApp</span><input name="whatsapp" value="{{ old('whatsapp', $vendor['whatsapp'] ?? '') }}" required placeholder="01xxxxxxxxx"></label>
                    <label><span class="acc-sub-label">Contact email</span><input type="email" name="contact_email" value="{{ old('contact_email', $vendor['contact_email'] ?? $p['email'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Instagram</span><input name="instagram" value="{{ old('instagram', $vendor['instagram'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Instagram followers</span><input type="number" min="0" name="instagram_followers" value="{{ old('instagram_followers', $vendor['instagram_followers'] ?? 0) }}" required></label>
                    <label><span class="acc-sub-label">TikTok URL</span><input name="tiktok_url" value="{{ old('tiktok_url', $vendor['tiktok_url'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">TikTok followers</span><input type="number" min="0" name="tiktok_followers" value="{{ old('tiktok_followers', $vendor['tiktok_followers'] ?? 0) }}" required></label>
                    <label><span class="acc-sub-label">Facebook URL</span><input name="facebook_url" value="{{ old('facebook_url', $vendor['facebook_url'] ?? '') }}" required></label>
                    <label><span class="acc-sub-label">Facebook followers</span><input type="number" min="0" name="facebook_followers" value="{{ old('facebook_followers', $vendor['facebook_followers'] ?? 0) }}" required></label>
                    <div
                        class="acc-sub-span-2 vj-address"
                        x-data="addressMapPicker({
                            mapsKey: @js(config('services.google.maps_key')),
                            searchUrl: @js(route('site.maps.search')),
                            reverseUrl: @js(route('site.maps.reverse')),
                            address: @js(old('address', $vendor['address'] ?? '')),
                            lat: @js((float) old('latitude', $vendor['latitude'] ?? 30.0444)),
                            lng: @js((float) old('longitude', $vendor['longitude'] ?? 31.2357)),
                        })"
                    >
                        <input type="hidden" name="address" x-model="address" required>
                        <input type="hidden" name="latitude" x-model="lat" required>
                        <input type="hidden" name="longitude" x-model="lng" required>

                        <span class="acc-sub-label">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                            Address
                        </span>

                        <button type="button" class="book-map-trigger vj-address-trigger" @click="openPicker()">
                            <span class="book-map-trigger-ico" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z"/><circle cx="12" cy="11" r="2.2"/></svg>
                            </span>
                            <span class="book-map-trigger-copy">
                                <strong x-text="preview"></strong>
                                <small x-text="hasPin ? coordsLabel : 'Search or drop a pin on the map'"></small>
                            </span>
                            <span class="book-map-trigger-cta">Open map</span>
                        </button>

                        <div class="book-map-modal" x-show="open" x-cloak @keydown.escape.window="closePicker()">
                            <div class="book-map-dialog" @click.stop>
                                <header class="book-map-head">
                                    <button type="button" class="ghost-btn" style="margin:0" @click="closePicker()">Back</button>
                                    <h3>Pick address</h3>
                                    <button type="button" class="ghost-btn" style="margin:0" @click="locate()" :disabled="locating" x-text="locating ? 'Locating…' : 'GPS'"></button>
                                </header>

                                <div class="book-map-search">
                                    <input
                                        type="search"
                                        x-ref="searchInput"
                                        x-model="search"
                                        placeholder="Search places in Egypt…"
                                        @keydown.enter.prevent="searchPlaces()"
                                    >
                                    <button type="button" class="ai-btn" @click="searchPlaces()" :disabled="searching" x-text="searching ? '…' : 'Search'"></button>
                                </div>

                                <div class="book-chip-row book-map-chips">
                                    <template x-for="place in (suggestions.length ? suggestions : places)" :key="place.label">
                                        <button type="button" class="book-chip" :class="draft.label === place.label && 'is-active'" @click="setPlace(place)" x-text="place.label"></button>
                                    </template>
                                </div>

                                <div class="book-map-canvas vj-gmap-canvas" x-ref="mapCanvas"></div>

                                <div class="book-map-foot">
                                    <div>
                                        <strong x-text="draft.label || 'Drop a pin'"></strong>
                                        <small class="lede" x-show="geocoding">Looking up address…</small>
                                        <small class="lede" x-show="!geocoding && draft.lat" x-text="`${Number(draft.lat).toFixed(5)}, ${Number(draft.lng).toFixed(5)}`"></small>
                                    </div>
                                    <button type="button" class="ai-btn" @click="confirm()">Use this address</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div
                x-show="step === 3"
                x-cloak
                class="vj-pane"
                x-data="vendorProjects({ projects: @js(old('projects', $vendor['projects'] ?? [])) })"
            >
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="m21 16-5-4-4 3-3-2-4 4"/></svg>
                    </span>
                    <div>
                        <h2>Previous projects</h2>
                        <p>Upload photos, a gallery, videos, or social links — same as the app and admin.</p>
                    </div>
                </header>

                <div class="vj-upload">
                    <span class="vj-upload-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V6m0 0 4 4m-4-4-4 4"/><path d="M4 18h16"/></svg>
                    </span>
                    <div>
                        <strong>Cover image</strong>
                        <p>Landscape works best. JPG or PNG.</p>
                    </div>
                    <input type="file" name="cover_image" accept="image/*">
                </div>

                <div class="vj-projects">
                    <template x-for="(project, index) in projects" :key="'project-' + index">
                        <article class="vj-project" data-vj-project>
                            <header class="vj-project-head">
                                <strong x-text="'Project ' + (index + 1)"></strong>
                                <button type="button" class="ghost-btn vj-project-remove" @click="remove(index)">Remove</button>
                            </header>

                            <div class="vj-project-type" role="group" :aria-label="'Project ' + (index + 1) + ' type'">
                                <template x-for="opt in [
                                    { id: 'image', label: 'Photo' },
                                    { id: 'video', label: 'Video' },
                                    { id: 'link', label: 'Social / link' },
                                ]" :key="opt.id">
                                    <button
                                        type="button"
                                        class="book-chip"
                                        :class="project.type === opt.id && 'is-active'"
                                        @click="project.type = opt.id"
                                        x-text="opt.label"
                                    ></button>
                                </template>
                            </div>
                            <input type="hidden" :name="'projects[' + index + '][type]'" :value="project.type">

                            <div class="acc-sub-grid vj-project-grid">
                                <label>
                                    <span class="acc-sub-label">Project title</span>
                                    <input type="text" data-vj-title :name="'projects[' + index + '][title]'" x-model="project.title" placeholder="Wedding film · Brand shoot">
                                </label>
                                <label>
                                    <span class="acc-sub-label">Completed on</span>
                                    <input type="text" :name="'projects[' + index + '][completed_on]'" x-model="project.completed_on" placeholder="2025 · March 2026">
                                </label>
                                <label class="acc-sub-span-2" x-show="project.type !== 'image'" x-cloak>
                                    <span class="acc-sub-label" x-text="project.type === 'link' ? 'Sample URL' : 'Sample URL (optional)'"></span>
                                    <input type="url" :name="'projects[' + index + '][external_url]'" x-model="project.external_url" placeholder="https://">
                                </label>
                                <label class="acc-sub-span-2">
                                    <span class="acc-sub-label">Description</span>
                                    <textarea :name="'projects[' + index + '][description]'" x-model="project.description" rows="2" placeholder="Brief about the job"></textarea>
                                </label>
                                <label class="acc-sub-check">
                                    <input type="checkbox" :name="'projects[' + index + '][is_featured]'" value="1" x-model="project.is_featured">
                                    <span>Featured on profile</span>
                                </label>
                            </div>

                            <template x-for="id in project.ids" :key="'id-' + index + '-' + id">
                                <input type="hidden" :name="'projects[' + index + '][ids][]'" :value="id">
                            </template>

                            <div class="vj-project-media" x-show="project.type !== 'link'" x-cloak>
                                <label class="vj-upload vj-upload-inline">
                                    <span class="vj-upload-ico" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V6m0 0 4 4m-4-4-4 4"/><path d="M4 18h16"/></svg>
                                    </span>
                                    <div>
                                        <strong x-text="project.type === 'video' ? 'Add videos' : 'Add photos / gallery'"></strong>
                                        <p x-text="project.type === 'video' ? 'MP4 or MOV · multiple allowed' : 'JPG or PNG · multiple allowed'"></p>
                                    </div>
                                    <input type="file" :name="'projects[' + index + '][files][]'" :accept="acceptFor(project.type)" multiple>
                                </label>

                                <div class="vj-project-files" x-show="project.existing.length" x-cloak>
                                    <template x-for="(file, fileIndex) in project.existing" :key="'file-' + index + '-' + fileIndex">
                                        <span class="vj-project-file">
                                            <span x-text="file.name"></span>
                                            <button type="button" @click="removeExisting(index, fileIndex)" aria-label="Remove media">×</button>
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </article>
                    </template>

                    <button type="button" class="ghost-btn vj-project-add" @click="add()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Add previous project
                    </button>
                </div>

                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div x-show="step === 4" x-cloak class="vj-pane">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2.5"/><path d="M3 10h18M7 15h4"/></svg>
                    </span>
                    <div>
                        <h2>Pricing</h2>
                        <p>Set the rates clients see when they book you.</p>
                    </div>
                </header>
                <div class="acc-sub-grid">
                    <label><span class="acc-sub-label">Half day price</span><input type="number" min="0" name="half_day_price" value="{{ old('half_day_price', $vendor['half_day_price'] ?? '') }}" placeholder="EGP"></label>
                    <label><span class="acc-sub-label">Full day price</span><input type="number" min="0" name="full_day_price" value="{{ old('full_day_price', $vendor['full_day_price'] ?? '') }}" placeholder="EGP"></label>
                    <label><span class="acc-sub-label">Hourly price</span><input type="number" min="0" name="hourly_price" value="{{ old('hourly_price', $vendor['hourly_price'] ?? '') }}" placeholder="EGP"></label>
                    <label><span class="acc-sub-label">Per video price</span><input type="number" min="0" name="per_video_price" value="{{ old('per_video_price', $vendor['per_video_price'] ?? '') }}" placeholder="EGP"></label>
                    <label><span class="acc-sub-label">Turnaround hours</span><input type="number" min="1" name="turnaround_hours" value="{{ old('turnaround_hours', $vendor['turnaround_hours'] ?? 48) }}"></label>
                </div>
                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div
                x-show="step === 5"
                x-cloak
                class="vj-pane"
                x-data="vendorAvailability()"
            >
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>
                    </span>
                    <div>
                        <h2>Availability</h2>
                        <p>Set your weekly working hours — clients book inside these windows.</p>
                    </div>
                </header>

                <div class="vj-cal">
                    <div class="vj-cal-toolbar">
                        <div class="vj-cal-summary">
                            <span class="vj-cal-summary-ico" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 7v5l3 2"/><circle cx="12" cy="12" r="8"/></svg>
                            </span>
                            <div>
                                <strong x-text="activeCount + ' open day' + (activeCount === 1 ? '' : 's') + ' · ' + (Number.isInteger(totalHours) ? totalHours : totalHours.toFixed(1)) + 'h / week'"></strong>
                                <small x-text="'Publishes the next ' + weeksAhead + ' weeks · refine anytime in Vendor studio'"></small>
                            </div>
                        </div>
                        <div class="vj-cal-stats" aria-hidden="true">
                            <span><em x-text="activeCount"></em> days</span>
                            <span><em x-text="Number.isInteger(totalHours) ? totalHours : totalHours.toFixed(1)"></em> hrs</span>
                        </div>
                    </div>

                    <div class="vj-cal-defaults">
                        <div class="vj-cal-defaults-copy">
                            <strong>Default hours</strong>
                            <small>Apply one schedule to every open day</small>
                        </div>
                        <div class="vj-cal-defaults-controls">
                            <label>
                                <span>From</span>
                                <select x-model="defaultStart">
                                    <template x-for="t in timeOptions" :key="'ds-' + t">
                                        <option :value="t" x-text="t"></option>
                                    </template>
                                </select>
                            </label>
                            <span class="vj-day-hours-sep" aria-hidden="true">→</span>
                            <label>
                                <span>To</span>
                                <select x-model="defaultEnd">
                                    <template x-for="t in timeOptions" :key="'de-' + t">
                                        <option :value="t" x-text="t"></option>
                                    </template>
                                </select>
                            </label>
                            <button type="button" class="ai-btn vj-cal-apply" @click="applyDefault()">Apply to open days</button>
                        </div>
                    </div>

                    <div class="vj-cal-presets" role="group" aria-label="Schedule presets">
                        <button type="button" class="ghost-btn" @click="applyPreset('standard')">Standard 10–18</button>
                        <button type="button" class="ghost-btn" @click="applyPreset('early')">Early 9–17</button>
                        <button type="button" class="ghost-btn" @click="applyPreset('late')">Late 12–20</button>
                        <button type="button" class="ghost-btn" @click="applyPreset('weekend')">Weekends</button>
                        <button type="button" class="ghost-btn" @click="setWeekdays()">Weekdays</button>
                        <button type="button" class="ghost-btn" @click="setAll(true)">All days</button>
                        <button type="button" class="ghost-btn" @click="setAll(false)">Clear</button>
                    </div>

                    <div class="vj-cal-legend" aria-hidden="true">
                        <span>6am</span><span>12pm</span><span>6pm</span><span>11pm</span>
                    </div>

                    <div class="vj-schedule" role="group" aria-label="Weekly working hours">
                        <template x-for="(day, i) in days" :key="day.key">
                            <div class="vj-sched-row" :class="{ 'is-on': day.on, 'is-invalid': day.on && dayHours(day) <= 0 }">
                                <button
                                    type="button"
                                    class="vj-sched-toggle"
                                    @click="day.on = !day.on"
                                    :aria-pressed="day.on.toString()"
                                    :aria-label="day.full + (day.on ? ' open' : ' closed')"
                                >
                                    <span class="vj-sched-switch" aria-hidden="true"></span>
                                </button>

                                <div class="vj-sched-day">
                                    <strong x-text="day.full"></strong>
                                    <small x-text="day.key"></small>
                                </div>

                                <div class="vj-sched-bar" aria-hidden="true">
                                    <span class="vj-sched-bar-track"></span>
                                    <span class="vj-sched-bar-fill" :style="barStyle(day)"></span>
                                </div>

                                <div class="vj-sched-times" x-show="day.on" x-cloak>
                                    <label>
                                        <span>From</span>
                                        <select x-model="day.start">
                                            <template x-for="t in timeOptions" :key="day.key + '-s-' + t">
                                                <option :value="t" x-text="t"></option>
                                            </template>
                                        </select>
                                    </label>
                                    <span class="vj-day-hours-sep" aria-hidden="true">→</span>
                                    <label>
                                        <span>To</span>
                                        <select x-model="day.end">
                                            <template x-for="t in timeOptions" :key="day.key + '-e-' + t">
                                                <option :value="t" x-text="t"></option>
                                            </template>
                                        </select>
                                    </label>
                                </div>
                                <div class="vj-sched-closed" x-show="!day.on" x-cloak>Closed</div>

                                <div class="vj-sched-meta">
                                    <span class="vj-sched-hours" x-text="dayHoursLabel(day)"></span>
                                    <button
                                        type="button"
                                        class="vj-sched-copy"
                                        x-show="day.on"
                                        x-cloak
                                        @click="copyToOpen(i)"
                                        title="Copy these hours to other open days"
                                    >Copy</button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <template x-for="(slot, index) in payloadSlots()" :key="'slot-' + index">
                        <div hidden>
                            <input type="hidden" :name="'availabilities[' + index + '][starts_at]'" :value="slot.starts_at">
                            <input type="hidden" :name="'availabilities[' + index + '][ends_at]'" :value="slot.ends_at">
                            <input type="hidden" :name="'availabilities[' + index + '][status]'" :value="slot.status">
                        </div>
                    </template>
                </div>

                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="button" @click="next()">Continue</button>
                </div>
            </div>

            <div x-show="step === 6" x-cloak class="vj-pane">
                <header class="acc-sub-card-head">
                    <span class="acc-sub-card-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M3 11h18M7 15h3"/></svg>
                    </span>
                    <div>
                        <h2>Payouts</h2>
                        <p>Where Lens sends your earnings after escrow release.</p>
                    </div>
                </header>
                <div class="acc-sub-grid">
                    <label><span class="acc-sub-label">Payout method</span>
                        <select name="payout_method" required>
                            <option value="bank" @selected(old('payout_method', $vendor['payout_method'] ?? '') === 'bank')>Bank</option>
                            <option value="wallet" @selected(old('payout_method', $vendor['payout_method'] ?? '') === 'wallet')>Wallet</option>
                            <option value="paypal" @selected(old('payout_method', $vendor['payout_method'] ?? '') === 'paypal')>PayPal</option>
                        </select>
                    </label>
                    <label><span class="acc-sub-label">Bank name</span><input name="bank_name" value="{{ old('bank_name', $vendor['bank_name'] ?? '') }}"></label>
                    <label><span class="acc-sub-label">Account holder</span><input name="bank_account_holder" value="{{ old('bank_account_holder', $vendor['bank_account_holder'] ?? '') }}"></label>
                    <label><span class="acc-sub-label">Account number</span><input name="bank_account_number" value="{{ old('bank_account_number', $vendor['bank_account_number'] ?? '') }}"></label>
                    <label><span class="acc-sub-label">Wallet phone</span><input name="wallet_phone" value="{{ old('wallet_phone', $vendor['wallet_phone'] ?? '') }}" placeholder="01xxxxxxxxx"></label>
                    <label><span class="acc-sub-label">PayPal email</span><input type="email" name="paypal_email" value="{{ old('paypal_email', $vendor['paypal_email'] ?? '') }}"></label>
                </div>
                @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
                <div class="vj-actions">
                    <button class="ghost-btn" type="button" @click="prev()">Back</button>
                    <button class="ai-btn" type="submit">{{ $editing ? 'Save profile' : 'Submit for verification' }}</button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
