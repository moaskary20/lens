<footer class="site-footer">
    <section class="foot-cta">
        <div class="wrap foot-cta-row">
            @include('site.partials.wordmark', ['size' => 'lg'])
            <div class="foot-cta-copy">
                <h2>Ready to Bring Your<br><span>Creative Vision to Life?</span></h2>
                <p>Join thousands of creators, photographers and videographers who are already on Lens.</p>
            </div>
            <a class="foot-cta-btn" href="{{ route('site.explore') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.4-3.4"/></svg>
                Explore Now
                <span>›</span>
            </a>
        </div>
    </section>

    <section class="wrap foot-grid">
        <div class="foot-brand">
            @include('site.partials.wordmark', ['size' => 'lg'])
            <p>The ultimate platform to connect with professional photographers, videographers, studios and creative talent. Turn your ideas into stunning visual stories.</p>
            <div class="foot-trust">
                <span>
                    <svg viewBox="0 0 24 24"><path d="M9 7h1.5l1-2h3l1 2H17a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h2z"/><circle cx="12" cy="13" r="3"/></svg>
                    Verified<br>Creators
                </span>
                <span>
                    <svg viewBox="0 0 24 24"><path d="M12 3 5 6v6c0 4.5 3 7.8 7 9 4-1.2 7-4.5 7-9V6z"/><path d="m9.2 12.2 1.8 1.8 3.8-3.8"/></svg>
                    Secure<br>Booking
                </span>
                <span>
                    <svg viewBox="0 0 24 24"><path d="M13 2 4 14h8l-1 8 9-12h-8l1-8z"/></svg>
                    Best<br>Prices
                </span>
                <span>
                    <svg viewBox="0 0 24 24"><path d="M4 12v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="12" width="5" height="7" rx="1.5"/><rect x="16" y="12" width="5" height="7" rx="1.5"/><path d="M21 16h1a2 2 0 0 1 0 4h-3"/></svg>
                    24/7<br>Support
                </span>
            </div>
            <form class="foot-news" action="{{ route('site.newsletter') }}" method="post">
                @csrf
                <strong>Subscribe to Our Newsletter</strong>
                <p>Get the latest updates, offers and creative inspiration.</p>
                <div class="foot-news-row">
                    <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    <input type="email" name="email" required placeholder="Enter your email address">
                    <button type="submit" aria-label="Subscribe">
                        <svg viewBox="0 0 24 24"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4z"/></svg>
                    </button>
                </div>
                @if(session('newsletter_ok'))
                    <em>Thanks — you’re on the list.</em>
                @endif
            </form>
        </div>

        <nav class="foot-col">
            <h3>Quick Links</h3>
            <a href="{{ route('site.home') }}"><span>›</span> Home</a>
            <a href="{{ route('site.search') }}"><span>›</span> Search</a>
            <a href="{{ route('site.bookings') }}"><span>›</span> Bookings</a>
            <a href="{{ route('site.explore') }}"><span>›</span> Explore</a>
            <a href="{{ route('site.page', 'about') }}"><span>›</span> About Us</a>
            <a href="{{ route('site.page', 'contact') }}"><span>›</span> Contact Us</a>
            <a href="{{ route('site.help') }}"><span>›</span> Help & Support</a>
            <a href="{{ route('site.page', 'terms') }}"><span>›</span> Terms & Conditions</a>
            <a href="{{ route('site.page', 'privacy') }}"><span>›</span> Privacy Policy</a>
        </nav>

        <nav class="foot-col foot-cats">
            <h3>Categories</h3>
            @foreach(collect($data['vendor_types'] ?? []) as $type)
                <a href="{{ route('site.search', ['vendor_type' => $type['slug'] ?? '']) }}">
                    @include('site.partials.category-icon', ['slug' => $type['slug'] ?? ''])
                    {{ $type['label'] ?? ($type['name'] ?? '') }}
                </a>
            @endforeach
            <a href="{{ route('site.explore') }}"><span>›</span> View all categories</a>
        </nav>

        <nav class="foot-col">
            <h3>For Creators</h3>
            <a href="/vendor/login"><span>›</span> Become a Vendor</a>
            <a href="{{ route('site.page', 'guidelines') }}"><span>›</span> Creator Guidelines</a>
            <a href="{{ route('site.page', 'pricing') }}"><span>›</span> Pricing & Commission</a>
            <a href="{{ route('site.page', 'resources') }}"><span>›</span> Creator Resources</a>
            <a href="{{ route('site.page', 'faqs') }}"><span>›</span> FAQs</a>
        </nav>

        <div class="foot-col foot-app">
            <h3>Download Our App</h3>
            <p>Get the best experience on your mobile device.</p>
            <a class="store-badge store-apple" href="#app">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#fff" d="M16.37 12.32c.02-2.2 1.8-3.26 1.88-3.31-1.03-1.5-2.63-1.71-3.2-1.73-1.36-.14-2.66.8-3.35.8-.7 0-1.76-.78-2.9-.76-1.49.02-2.87.87-3.64 2.2-1.55 2.69-.4 6.67 1.11 8.86.74 1.07 1.62 2.27 2.77 2.23 1.11-.05 1.53-.72 2.88-.72 1.34 0 1.72.72 2.9.69 1.2-.02 1.95-1.08 2.68-2.16.84-1.23 1.19-2.42 1.21-2.48-.03-.01-2.32-.89-2.34-3.52zM14.7 6.3c.61-.74 1.02-1.77.91-2.8-.88.04-1.94.59-2.57 1.32-.56.65-1.06 1.69-.93 2.69.98.08 1.98-.5 2.59-1.21z"/></svg>
                <span><small>Download on the</small><strong>App Store</strong></span>
            </a>
            <a class="store-badge store-play" href="https://play.google.com/store/apps/details?id=app.lens.lens" target="_blank" rel="noopener noreferrer">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M3.4 20.7V3.3c0-.5.3-.8.7-.9l10.4 9.6L3.4 20.7z"/>
                    <path fill="#34A853" d="m14.5 12 2.5 2.4-8.8 5.1c-.6.3-1.2.1-1.5-.3L14.5 12z"/>
                    <path fill="#FBBC04" d="M19.2 10.6c.5.3.8.7.8 1.4s-.3 1.1-.8 1.4l-2.2 1.3-2.5-2.7 2.5-2.6 2.2 1.2z"/>
                    <path fill="#EA4335" d="M8.2 4.5 17 9.6l-2.5 2.4L4.2 2.8c.3-.3.8-.4 1.3-.2l2.7 1.9z"/>
                </svg>
                <span><small>GET IT ON</small><strong>Google Play</strong></span>
            </a>
            <div class="foot-qr-wrap">
                <p class="qr-caption">Scan &amp; Download</p>
                <svg class="qr-arrow" viewBox="0 0 48 36" aria-hidden="true">
                    <path d="M2 4c12 1 22 7 28 20"/>
                    <path d="M22 16c2 5 6 8 11 9"/>
                    <path d="M24 26.5 35 27.5 29.5 18"/>
                </svg>
                <img class="foot-qr" src="{{ asset('branding/app-qr.png') }}" alt="Download Lens app">
            </div>
        </div>

        <div class="foot-col foot-follow">
            <h3>Follow Us</h3>
            <p>Stay connected for the latest updates, inspiration and more.</p>
            <div class="foot-social">
                <a href="https://www.facebook.com/profile.php?id=61595790683189" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M14 8h3M13.2 8.2V6.4A2.4 2.4 0 0 1 15.6 4h1.2M10 12h5.2M10 12v7M13.2 8.2V19"/></svg></a>
                <a href="https://www.instagram.com/lensapp2026?srtk=amc0MmE3NWpleWR1" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.5"/><circle cx="17.2" cy="6.8" r=".8" fill="currentColor" stroke="none"/></svg></a>
                <a href="https://www.tiktok.com/@lens.app?_r=1&_t=ZS-9B4RZFl5Up7" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><svg viewBox="0 0 24 24"><path d="M14 7c.8 2.4 2.4 3.8 4.6 4v2.3A7 7 0 0 1 14 12v4.2a4.2 4.2 0 1 1-4.2-4.2c.3 0 .6 0 .8.1v2.4a1.8 1.8 0 1 0 1.2 1.7V7H14z"/></svg></a>
            </div>
            @php
                $stats = $siteStats ?? \App\Support\SiteStats::marketplace();
            @endphp
            <ul class="foot-stats">
                <li>
                    <span class="stat-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor" fill-rule="evenodd">
                            <path d="M9.15 5.2h1l.55-1.05A1.2 1.2 0 0 1 11.75 3.5h.5a1.2 1.2 0 0 1 1.05.65l.55 1.05h1a2.85 2.85 0 0 1 2.85 2.85v8.1A2.85 2.85 0 0 1 14.85 19H9.15A2.85 2.85 0 0 1 6.3 16.15v-8.1A2.85 2.85 0 0 1 9.15 5.2Zm2.85 10.55a3.35 3.35 0 1 0 0-6.7 3.35 3.35 0 0 0 0 6.7Zm0-2.1a1.25 1.25 0 1 0 0-2.5 1.25 1.25 0 0 0 0 2.5Z"/>
                        </svg>
                    </span>
                    <div>
                        <strong>{{ $stats['creators_label'] }}</strong>
                        <span>Active Creators</span>
                    </div>
                </li>
                <li>
                    <span class="stat-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 3.8a3.4 3.4 0 1 1 0 6.8 3.4 3.4 0 0 1 0-6.8Zm7.15 1.35a2.7 2.7 0 1 1 0 5.4 2.7 2.7 0 0 1 0-5.4ZM2.55 19.4c.7-3.55 3.35-5.55 6.45-5.55s5.75 2 6.45 5.55H2.55Zm11.85-.55c.55-2.35 2.2-3.85 4.55-4.05 1.15.2 2.05.75 2.55 1.55.55.85.8 1.9.9 2.5h-8Z"/>
                        </svg>
                    </span>
                    <div>
                        <strong>{{ $stats['clients_label'] }}</strong>
                        <span>Happy Clients</span>
                    </div>
                </li>
                <li>
                    <span class="stat-ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="m12 2.4 2.95 5.95 6.55.95-4.75 4.65 1.12 6.55L12 17.4l-5.87 3.1 1.12-6.55-4.75-4.65 6.55-.95z"/>
                        </svg>
                    </span>
                    <div>
                        <strong>{{ $stats['rating_label'] }}</strong>
                        <span>Average Rating{{ ($stats['reviews'] ?? 0) > 0 ? ' · '.number_format((int) $stats['reviews']).' reviews' : '' }}</span>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <section class="foot-bottom">
        <div class="foot-bottom-photo">
            <div class="wrap foot-bar">
                <div class="foot-bar-row">
                    @include('site.partials.wordmark', ['size' => 'sm'])
                    <p class="foot-copy">© {{ now()->year }} Lens. All rights reserved.</p>
                    <div class="foot-bar-trust">
                        <span>
                            <svg viewBox="0 0 24 24"><path d="M12 3 5 6v6c0 4.5 3 7.8 7 9 4-1.2 7-4.5 7-9V6z"/><path d="m9.2 12.2 1.8 1.8 3.8-3.8"/></svg>
                            Safe &amp; Secure<br>Payments
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24"><rect x="3" y="8" width="18" height="12" rx="2"/><path d="M3 12h18M7 15.5h3"/></svg>
                            Multiple<br>Payment Methods
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.4 2.2 3.6 5.2 3.6 8.5s-1.2 6.3-3.6 8.5c-2.4-2.2-3.6-5.2-3.6-8.5s1.2-6.3 3.6-8.5z"/></svg>
                            Worldwide<br>Coverage
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24"><path d="M4 12a8 8 0 0 1 16 0"/><rect x="3" y="12" width="4.5" height="6.5" rx="1.5"/><rect x="16.5" y="12" width="4.5" height="6.5" rx="1.5"/></svg>
                            24/7<br>Customer Support
                        </span>
                    </div>
                    <div class="foot-pay" aria-label="We Accept">
                        <small>We Accept</small>
                        <div class="foot-pay-logos">
                            <b class="pay-visa">VISA</b>
                            <b class="pay-mc"><i></i><i></i></b>
                            <b class="pay-pp">PayPal</b>
                            <b class="pay-ap"><svg viewBox="0 0 14 16" aria-hidden="true"><path fill="#fff" d="M11.2 8.4c0-1.6 1.3-2.4 1.4-2.4-.8-1.1-2-1.3-2.4-1.3-1-.1-2 .6-2.5.6-.5 0-1.3-.6-2.2-.6-1.1 0-2.2.7-2.7 1.7-1.2 2-.3 5 .8 6.6.6.8 1.2 1.7 2.1 1.7.8 0 1.1-.5 2.2-.5s1.3.5 2.2.5 1.5-.8 2-1.6c.6-.9.9-1.8.9-1.8s-1.8-.7-1.8-2.9zM9.4 3.8c.4-.5.7-1.3.6-2-.6 0-1.4.4-1.8.9-.4.5-.7 1.2-.6 1.9.7.1 1.4-.3 1.8-.8z"/></svg>Pay</b>
                        </div>
                    </div>
                </div>
                <div class="foot-tagline">
                    <svg class="foot-curve" viewBox="0 0 920 36" preserveAspectRatio="none" aria-hidden="true">
                        <path d="M0 18 H420 C520 18 560 30 680 30 H920" fill="none" stroke="#ff5722" stroke-width="2.4" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="wrap foot-community-wrap">
            <p class="foot-community">More than just a booking platform <span>→</span> It’s a creative community.</p>
        </div>
    </section>
</footer>
