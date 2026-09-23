<div class="lens-loop">
    <div class="lens-loop__head">
        <p class="lens-loop__eyebrow">Marketplace loop</p>
        <h2 class="lens-loop__title">Client books a vendor. Vendor delivers on Lens.</h2>
        <p class="lens-loop__lead">The full product idea lives in this panel: users, vendors by category, previous work, hours, prices, client projects, and in-app file delivery.</p>
    </div>

    <ol class="lens-loop__steps">
        @foreach ($steps as $step)
            <li class="lens-loop__step">
                <a href="{{ $step['url'] }}" class="lens-loop__card">
                    <span class="lens-loop__num">{{ $step['num'] }}</span>
                    <strong class="lens-loop__name">{{ $step['title'] }}</strong>
                    <span class="lens-loop__count">{{ number_format($step['count']) }} <small>{{ $step['unit'] }}</small></span>
                    <p class="lens-loop__text">{{ $step['text'] }}</p>
                </a>
                @if (! empty($step['links']))
                    <div class="lens-loop__links">
                        @foreach ($step['links'] as $link)
                            <a href="{{ $link['url'] }}">{{ $link['label'] }} · {{ number_format($link['count']) }}</a>
                        @endforeach
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
</div>
