@extends('site.layout', ['title' => 'Create account', 'active' => 'account'])

@section('content')
<section class="auth-page wrap" data-reveal>
    <div class="site-shell-card" style="max-width:640px;margin-inline:auto">
        <h1 style="margin:0 0 8px">Create your client account</h1>
        <p class="lede">Same credentials work in the Lens mobile app.</p>

        <form method="post" action="{{ route('site.register.submit') }}" class="site-shell-form" style="margin-top:18px">
            @csrf
            <input type="hidden" name="role" value="client">
            <label>
                <span>Full name</span>
                <input name="name" value="{{ old('name') }}" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required>
            </label>
            <label>
                <span>Phone</span>
                <input name="phone" value="{{ old('phone') }}" required placeholder="01xxxxxxxxx">
            </label>
            <label>
                <span>Governorate</span>
                <select name="city_id" required>
                    <option value="">Choose…</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>{{ $city->name_en }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Language</span>
                <select name="locale">
                    <option value="en" @selected(old('locale', 'en') === 'en')>English</option>
                    <option value="ar" @selected(old('locale') === 'ar')>العربية</option>
                </select>
            </label>
            @foreach($errors->all() as $error)
                <em class="bookings-error">{{ $error }}</em>
            @endforeach
            <button class="ai-btn" type="submit">Create account</button>
        </form>
        <p style="margin-top:16px"><a href="{{ route('site.login') }}" style="color:var(--orange);font-weight:700">Already have an account?</a></p>
    </div>
</section>
@endsection
