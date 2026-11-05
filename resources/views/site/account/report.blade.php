@extends('site.layout', ['title' => 'Report an issue', 'active' => 'account'])

@section('content')
<section class="account-page wrap" data-reveal>
    <div class="site-shell-card" style="max-width:640px">
        <h1 style="margin-top:0">Report an issue</h1>
        <form method="post" action="{{ route('site.account.report.store') }}" class="site-shell-form">
            @csrf
            <label><span>Topic</span>
                <select name="topic" required>
                    @foreach(\App\Models\IssueReport::TOPICS as $value => $label)
                        <option value="{{ $value }}" @selected(old('topic') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label><span>Subject</span><input name="subject" value="{{ old('subject') }}" required></label>
            <label><span>Details</span><textarea name="body" required>{{ old('body') }}</textarea></label>
            @foreach($errors->all() as $error)<em class="bookings-error">{{ $error }}</em>@endforeach
            <button class="ai-btn" type="submit">Send report</button>
        </form>
    </div>
</section>
@endsection
