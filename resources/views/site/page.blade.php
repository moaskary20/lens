@extends('site.layout', ['title' => $title, 'active' => $active ?? 'home'])

@section('content')
<section class="page-head wrap">
    <h1>{{ $title }}</h1>
    <p class="lede">{{ $lede }}</p>
</section>
<section class="wrap block">
    <p class="lede">{{ $body }}</p>
</section>
@endsection
