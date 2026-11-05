@extends('site.layout', ['title' => 'Edit profile', 'active' => 'account'])

@section('content')
<section class="account-page wrap" data-reveal>
    <div class="site-shell-card" style="max-width:640px">
        <h1 style="margin-top:0">Edit profile</h1>
        <form method="post" action="{{ route('site.account.update') }}" class="site-shell-form" enctype="multipart/form-data">
            @csrf
            <label><span>Name</span><input name="name" value="{{ old('name', $profile['name'] ?? '') }}" required></label>
            <label><span>Phone</span><input name="phone" value="{{ old('phone', $profile['phone'] ?? '') }}"></label>
            <label>
                <span>Governorate</span>
                <select name="city_id">
                    <option value="">Choose…</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" @selected(old('city_id', $profile['city_id'] ?? null) == $city->id)>{{ $city->name_en }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Language</span>
                <select name="locale">
                    <option value="en" @selected(old('locale', $profile['locale'] ?? 'en') === 'en')>English</option>
                    <option value="ar" @selected(old('locale', $profile['locale'] ?? '') === 'ar')>العربية</option>
                </select>
            </label>
            @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
            <button class="ai-btn" type="submit">Save</button>
        </form>
    </div>
</section>
@endsection
