<?php

namespace App\Support;

use App\Models\CmsPage;
use App\Models\Setting;
use App\Models\VendorType;
use Illuminate\Support\Str;

class SitePages
{
    /**
     * @return array{
     *     title: string,
     *     lede: string,
     *     body: string,
     *     html: string,
     *     hero: string,
     *     image: string,
     *     gallery: list<string>,
     *     slug: string,
     *     supportEmail: string,
     *     sections: list<array{title: string, body?: string, items?: list<string>, image?: string}>
     * }
     */
    public static function resolve(string $slug): array
    {
        $slug = self::normalizeSlug($slug);
        $cms = CmsPage::query()->where('slug', $slug)->where('is_active', true)->first();
        $platform = Setting::groupValues('platform');
        $finance = Finance::settings();
        $appName = (string) ($platform['app_name'] ?? 'Lens');
        $supportEmail = (string) ($platform['support_email'] ?? 'support@lens.app');
        $supportPhone = trim((string) ($platform['support_phone'] ?? ''));
        $about = trim((string) ($platform['about'] ?? ''));

        $defaults = self::defaults($appName, $about, $supportEmail, $supportPhone, $finance);
        abort_unless(isset($defaults[$slug]), 404);

        $base = $defaults[$slug];
        $html = filled($cms?->body) ? (string) $cms->body : (string) $base['html'];
        $title = filled($cms?->title) ? (string) $cms->title : (string) $base['title'];

        return [
            'title' => $title,
            'lede' => (string) $base['lede'],
            'body' => trim(Str::of(strip_tags($html))->squish()),
            'html' => $html,
            'hero' => (string) $base['hero'],
            'image' => (string) $base['image'],
            'gallery' => array_values($base['gallery'] ?? []),
            'slug' => $slug,
            'supportEmail' => $supportEmail,
            'sections' => $base['sections'],
        ];
    }

    public static function normalizeSlug(string $slug): string
    {
        return match ($slug) {
            'terms-of-service', 'tos' => 'terms',
            'privacy-policy' => 'privacy',
            'creator-guidelines' => 'guidelines',
            'pricing-commission', 'commission' => 'pricing',
            'creator-resources' => 'resources',
            'faq' => 'faqs',
            default => $slug,
        };
    }

    /**
     * @param  array<string, mixed>  $finance
     * @return array<string, array{title: string, lede: string, html: string, hero: string, image: string, gallery: list<string>, sections: list<array{title: string, body?: string, items?: list<string>, image?: string}>}>
     */
    protected static function defaults(string $appName, string $about, string $supportEmail, string $supportPhone, array $finance): array
    {
        $currency = (string) ($finance['currency'] ?? 'EGP');
        $clientFee = (float) ($finance['client_fee_percent'] ?? 10);
        $vendorCommission = (float) ($finance['vendor_commission_percent'] ?? 20);
        $tax = (float) ($finance['tax_percent'] ?? 0);
        $disputeClient = (float) ($finance['dispute_client_percent'] ?? 80);
        $disputeVendor = (float) ($finance['dispute_vendor_percent'] ?? 10);
        $disputePlatform = (float) ($finance['dispute_platform_percent'] ?? 10);
        $types = VendorType::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name_en')->get();
        $typeLines = $types->map(function (VendorType $type): string {
            $rule = $type->escrow_on_checkin
                ? 'escrow releases on check-in / arrival'
                : 'escrow holds until deliverables + client Approve';

            return $type->name_en.' — '.$rule;
        })->all();
        $categoryNames = $types->pluck('name_en')->filter()->values()->all();
        $aboutText = $about !== ''
            ? $about
            : $appName.' is a multi-vendor marketplace connecting clients with photographers, studios, and creators through escrow-backed bookings and protected delivery.';
        $phoneLine = $supportPhone !== '' ? '<p><strong>Phone:</strong> '.e($supportPhone).'</p>' : '';
        $platformSettings = Setting::groupValues('platform');

        return [
            'about' => [
                'title' => 'About Us',
                'lede' => $appName.' — find, book, and create with verified talent.',
                'hero' => '/branding/hero-egypt-creators.jpg',
                'image' => '/branding/categories/photographer.jpg',
                'gallery' => [
                    '/branding/categories/photographer.jpg',
                    '/branding/categories/studio.jpg',
                    '/branding/categories/videographer.jpg',
                    '/branding/categories/model.jpg',
                    '/branding/categories/reels.jpg',
                ],
                'html' => '<p>'.e($aboutText).'</p>'
                    .'<p>'.$appName.' brings clients and creative vendors together in one protected marketplace across Egypt. Browse categories, compare packages, book in minutes, and keep payment in escrow until the work is approved.</p>',
                'sections' => [
                    [
                        'title' => 'What we offer',
                        'body' => 'Marketplace categories managed from Admin → Vendor Types.',
                        'image' => '/branding/categories/photographer.jpg',
                        'items' => $categoryNames !== [] ? $categoryNames : ['Photographers', 'Videographers', 'Studios', 'Models', 'UGC Creators'],
                    ],
                    [
                        'title' => 'How Lens protects bookings',
                        'body' => 'Checkout holds funds in the Lens escrow wallet. Release follows the live vendor-type rule from Fees & escrow and Vendor types.',
                        'image' => '/branding/categories/studio.jpg',
                        'items' => [
                            'Client pays session price + platform fee'.($tax > 0 ? ' + tax' : '').'.',
                            '100% of collected funds stay in escrow until a release condition fires.',
                            'Verified creators only appear in the public marketplace.',
                        ],
                    ],
                    [
                        'title' => 'Built for Egypt’s creative scene',
                        'body' => 'From Cairo studios to on-location shoots, Lens keeps brief, chat, and payment in one place.',
                        'image' => '/branding/hero-pyramids.jpg',
                        'items' => [
                            'Verified profiles with real portfolio work',
                            'Escrow-backed checkout on web and mobile',
                            'Support desk for disputes, payouts, and delivery issues',
                        ],
                    ],
                    [
                        'title' => 'Contact',
                        'body' => 'Reach the Lens desk any time.',
                        'image' => '/branding/categories/ugc.jpg',
                        'items' => array_values(array_filter([
                            'Email: '.$supportEmail,
                            $supportPhone !== '' ? 'Phone: '.$supportPhone : null,
                            'In-app / web Help & Support for issue reports',
                        ])),
                    ],
                ],
            ],
            'contact' => [
                'title' => 'Contact Us',
                'lede' => 'We are here when the shoot needs a human.',
                'hero' => '/branding/hero-egypt-studio.jpg',
                'image' => '/branding/categories/studio.jpg',
                'gallery' => [
                    '/branding/hero-egypt-studio.jpg',
                    '/branding/categories/studio.jpg',
                    '/branding/categories/bloggers.jpg',
                    '/branding/categories/voice.jpg',
                ],
                'html' => '<p>Staff review bookings, disputes, payouts, and issue reports from the admin console.</p>'
                    .'<p><strong>Support email:</strong> <a href="mailto:'.e($supportEmail).'">'.e($supportEmail).'</a></p>'
                    .$phoneLine
                    .'<p>You can also open <a href="'.e(route('site.help')).'">Help &amp; Support</a> to send a topic-based report (Booking, Payment, Account, App bug, Creator, Other).</p>',
                'sections' => [
                    [
                        'title' => 'Booking issues',
                        'body' => 'Schedule changes, no-shows, or delivery timing.',
                        'image' => '/branding/categories/photographer.jpg',
                        'items' => [
                            'Include the booking reference when you can',
                            'Share what changed on set or in chat',
                            'Use Help & Support → Booking for the fastest triage',
                        ],
                    ],
                    [
                        'title' => 'Payments & payouts',
                        'body' => 'Escrow holds, refunds, wallet, and creator payouts.',
                        'image' => '/branding/categories/food_stylist.jpg',
                        'items' => [
                            'Payment — escrow status, refunds, client wallet',
                            'Creator payouts after release conditions fire',
                            'Email '.$supportEmail.' with the booking ID if needed',
                        ],
                    ],
                    [
                        'title' => 'Account & verification',
                        'body' => 'Login, profile edits, and creator verification.',
                        'image' => '/branding/categories/model.jpg',
                        'items' => [
                            'Account — login, profile, notification settings',
                            'Creators — portfolio, packages, availability, ID checks',
                            'App bug — crashes or broken screens with device details',
                        ],
                    ],
                ],
            ],
            'terms' => [
                'title' => 'Terms & Conditions',
                'lede' => 'How '.$appName.' bookings, payments, and delivery work.',
                'hero' => '/branding/hero-pyramids.jpg',
                'image' => '/branding/categories/videographer.jpg',
                'gallery' => [
                    '/branding/hero-pyramids.jpg',
                    '/branding/categories/videographer.jpg',
                    '/branding/categories/studio.jpg',
                    '/branding/categories/ai_motion.jpg',
                ],
                'html' => '<p>By using '.$appName.' you agree to book through the marketplace, pay into escrow, and follow package, cancellation, and delivery rules published on each creator profile.</p>'
                    .'<p>Misuse, no-shows, unpaid penalties, and unresolved disputes are handled by Lens staff from the admin booking and dispute desks.</p>',
                'sections' => [
                    [
                        'title' => 'Bookings',
                        'body' => 'A booking is confirmed after successful checkout. Contact details unlock after confirmation.',
                        'image' => '/branding/categories/reels.jpg',
                        'items' => [
                            'Packages and starting prices come from the creator’s pricing model.',
                            'Travel fees may apply for out-of-governorate sessions when enabled on the vendor.',
                            'Cancellations follow the booking status and staff policy tools.',
                        ],
                    ],
                    [
                        'title' => 'Escrow & release',
                        'body' => 'Funds are held according to Admin → Fees & escrow and each vendor type’s release flag.',
                        'image' => '/branding/categories/studio.jpg',
                        'items' => $typeLines !== [] ? $typeLines : [
                            'Studios & models: release on check-in / arrival',
                            'Photographers & videographers: hold until deliverables + Approve',
                        ],
                    ],
                    [
                        'title' => 'Acceptable use',
                        'body' => 'Creators must keep portfolio, availability, and identity checks accurate. Clients must provide a clear brief and use escrow for marketplace bookings.',
                        'image' => '/branding/categories/photographer.jpg',
                        'items' => [
                            'No off-platform payment requests for marketplace jobs',
                            'Portfolio must show real work you can deliver',
                            'Abuse, fraud, or harassment can suspend accounts',
                        ],
                    ],
                    [
                        'title' => 'Disputes',
                        'body' => 'Quality & Support reviews booking, chat, deliverables, and escrow before deciding.',
                        'image' => '/branding/categories/ugc.jpg',
                        'items' => [
                            'Open a dispute from the booking with clear evidence',
                            'Unresolved splits follow live Fees & escrow settings',
                            'Staff decisions are final for marketplace escrow funds',
                        ],
                    ],
                ],
            ],
            'privacy' => [
                'title' => 'Privacy Policy',
                'lede' => 'How '.$appName.' stores client and vendor data.',
                'hero' => '/branding/hero-egypt-studio.jpg',
                'image' => '/branding/categories/ugc.jpg',
                'gallery' => [
                    '/branding/hero-egypt-studio.jpg',
                    '/branding/categories/ugc.jpg',
                    '/branding/categories/voice.jpg',
                    '/branding/categories/bloggers.jpg',
                ],
                'html' => '<p>'.$appName.' stores account, booking, chat, payout, and support data needed to run sessions. Full card numbers are never saved in Lens.</p>'
                    .'<p>Contact numbers stay hidden until a booking is confirmed. We do not sell client lists.</p>',
                'sections' => [
                    [
                        'title' => 'Data we process',
                        'body' => 'From Platform settings, account, and booking systems.',
                        'image' => '/branding/categories/ugc.jpg',
                        'items' => [
                            'Profile details, phone, email, and city',
                            'Booking briefs, deliverables references, and chat related to a job',
                            'Device tokens for push alerts when enabled',
                            'Issue reports and dispute records for Quality & Support',
                        ],
                    ],
                    [
                        'title' => 'Retention',
                        'body' => 'Client project files follow Admin → Platform settings storage rules.',
                        'image' => '/branding/categories/ai_motion.jpg',
                        'items' => [
                            'Default client project quota: '.(int) ($platformSettings['client_project_quota_mb'] ?? 2048).' MB',
                            'Auto-delete client project files after '.(int) ($platformSettings['client_project_retention_days'] ?? 7).' days from closed bookings',
                        ],
                    ],
                    [
                        'title' => 'Sharing & security',
                        'body' => 'We share only what a confirmed booking needs.',
                        'image' => '/branding/categories/studio.jpg',
                        'items' => [
                            'Creator contact unlocks after booking confirmation',
                            'Payment processors handle card data — Lens never stores full PANs',
                            'Staff access support and dispute records to resolve issues',
                        ],
                    ],
                    [
                        'title' => 'Your choices',
                        'body' => 'Update profile and notification settings from Account, or contact '.$supportEmail.' for support requests.',
                        'image' => '/branding/categories/model.jpg',
                        'items' => [
                            'Edit profile and notification preferences in Account',
                            'Request support deletes or corrections via '.$supportEmail,
                            'Disable push tokens by signing out or clearing device settings',
                        ],
                    ],
                ],
            ],
            'faqs' => [
                'title' => 'FAQs',
                'lede' => 'The questions we hear first.',
                'hero' => '/branding/hero-egypt-creators.jpg',
                'image' => '/branding/categories/reels.jpg',
                'gallery' => [
                    '/branding/hero-egypt-creators.jpg',
                    '/branding/categories/reels.jpg',
                    '/branding/categories/food_stylist.jpg',
                    '/branding/categories/videographer.jpg',
                    '/branding/categories/studio.jpg',
                ],
                'html' => '<p>Browse as a guest, book from web or the Lens app, and keep payment protected in escrow until approval or check-in depending on the creator type.</p>',
                'sections' => [
                    [
                        'title' => 'Can I browse without an account?',
                        'body' => 'Yes. Guest browsing is enabled when Allow guest is on in Platform settings. Create an account when you are ready to book.',
                        'image' => '/branding/categories/bloggers.jpg',
                    ],
                    [
                        'title' => 'When do I get the creator’s phone / WhatsApp?',
                        'body' => 'After the booking is confirmed. Contact details stay hidden until checkout succeeds.',
                        'image' => '/branding/categories/model.jpg',
                    ],
                    [
                        'title' => 'Which categories are listed?',
                        'body' => 'Active Vendor Types from the admin marketplace catalog — photographers, studios, models, UGC, and more as staff enable them.',
                        'image' => '/branding/categories/photographer.jpg',
                    ],
                    [
                        'title' => 'What do I pay at checkout?',
                        'body' => 'Session price + '.$clientFee.'% client platform fee'.($tax > 0 ? ' + '.$tax.'% tax' : '').' in '.$currency.'.',
                        'image' => '/branding/categories/studio.jpg',
                    ],
                    [
                        'title' => 'When does the creator get paid?',
                        'body' => 'After the live escrow release rule for their vendor type — either client Approve after deliverables, or check-in / arrival for types that release on arrival.',
                        'image' => '/branding/categories/videographer.jpg',
                    ],
                    [
                        'title' => 'What is the vendor commission?',
                        'body' => $vendorCommission.'% of the session price, taken only when funds are released successfully. Clients do not pay this commission at checkout.',
                        'image' => '/branding/categories/food_stylist.jpg',
                    ],
                    [
                        'title' => 'Do photographers need uploaded deliverables before Approve?',
                        'body' => ! empty($finance['release_requires_deliverables'])
                            ? 'Yes — deliverables are required before client Approve releases escrow.'
                            : 'Configured in Fees & escrow. Check the booking flow for the creator type you booked.',
                        'image' => '/branding/categories/reels.jpg',
                    ],
                    [
                        'title' => 'What if work is rejected and unresolved?',
                        'body' => 'Default split is client '.$disputeClient.'% / vendor '.$disputeVendor.'% / Lens '.$disputePlatform.'%, from Fees & escrow dispute settings.',
                        'image' => '/branding/categories/ugc.jpg',
                    ],
                    [
                        'title' => 'How do I contact support?',
                        'body' => 'Email '.$supportEmail.' or open Help & Support → Report an issue with a topic and booking reference.',
                        'image' => '/branding/categories/voice.jpg',
                    ],
                ],
            ],
            'pricing' => [
                'title' => 'Pricing & Commission',
                'lede' => 'Live fees from Admin → Fees & escrow.',
                'hero' => '/branding/categories/studio.jpg',
                'image' => '/branding/categories/food_stylist.jpg',
                'gallery' => [
                    '/branding/categories/studio.jpg',
                    '/branding/categories/food_stylist.jpg',
                    '/branding/categories/photographer.jpg',
                    '/branding/categories/ai_motion.jpg',
                ],
                'html' => '<p>Clients see starting-from prices on each profile. Checkout adds the client platform fee'.($tax > 0 ? ' and tax' : '').'. Lens commission is taken from the creator only when funds are released successfully.</p>',
                'sections' => [
                    [
                        'title' => 'Client checkout',
                        'body' => 'Currency: '.$currency.'.',
                        'image' => '/branding/categories/studio.jpg',
                        'items' => [
                            'Client platform fee: '.$clientFee.'%',
                            'Tax: '.$tax.'%',
                            'Example: 1,800 session → client fee '.number_format(1800 * $clientFee / 100, 0).' → total before tax '.number_format(1800 * (1 + $clientFee / 100), 0).' '.$currency,
                            ! empty($finance['hold_full_amount'])
                                ? '100% of collected funds are held in the Lens escrow wallet at checkout.'
                                : 'Escrow hold follows finance settings.',
                        ],
                    ],
                    [
                        'title' => 'Creator payout',
                        'body' => 'Vendor platform commission: '.$vendorCommission.'% of the session price (not charged at client checkout).',
                        'image' => '/branding/categories/photographer.jpg',
                        'items' => array_merge([
                            'Example: 1,800 session → creator net '.number_format(1800 * (1 - $vendorCommission / 100), 0).' '.$currency.' after commission.',
                            ! empty($finance['keep_hold_during_revisions'])
                                ? 'Funds stay on hold during revision cycles.'
                                : 'Revision hold follows finance settings.',
                        ], $typeLines),
                    ],
                    [
                        'title' => 'Unresolved dispute split',
                        'body' => 'Used when work is rejected and unresolved (Fees & escrow → Dispute split).',
                        'image' => '/branding/categories/videographer.jpg',
                        'items' => [
                            'Client refund: '.$disputeClient.'%',
                            'Vendor time compensation: '.$disputeVendor.'%',
                            'Lens admin fee: '.$disputePlatform.'%',
                        ],
                    ],
                ],
            ],
            'guidelines' => [
                'title' => 'Creator Guidelines',
                'lede' => 'What great '.$appName.' profiles look like.',
                'hero' => '/branding/categories/photographer.jpg',
                'image' => '/branding/categories/model.jpg',
                'gallery' => [
                    '/branding/categories/photographer.jpg',
                    '/branding/categories/model.jpg',
                    '/branding/categories/reels.jpg',
                    '/branding/categories/ugc.jpg',
                ],
                'html' => '<p>Creators publish from the vendor console / app: cover, portfolio projects, packages, availability, and payout details. Marketplace listing requires an active profile and Verification = Verified.</p>',
                'sections' => [
                    [
                        'title' => 'Profile checklist',
                        'body' => 'Aligned with Admin vendor onboarding and My Profile.',
                        'image' => '/branding/categories/model.jpg',
                        'items' => [
                            'Add a clear profile photo and cover image',
                            'Publish portfolio projects with real work',
                            'Keep packages and pricing current for your vendor type',
                            'Set open availability slots clients can book',
                            'Complete identity / verification so staff can approve you',
                        ],
                    ],
                    [
                        'title' => 'On the job',
                        'body' => 'Reply fast, show real work, and deliver through Lens.',
                        'image' => '/branding/categories/videographer.jpg',
                        'items' => [
                            'Respond to briefs and chat promptly',
                            'Upload deliverables when your type requires Approve-to-release',
                            'Use disputes only with clear evidence — Quality & Support reviews booking, chat, and escrow',
                            'Keep payout methods accurate for released funds',
                        ],
                    ],
                    [
                        'title' => 'Storage limits',
                        'body' => 'Portfolio quotas come from Platform settings.',
                        'image' => '/branding/categories/ai_motion.jpg',
                        'items' => [
                            'Default portfolio quota: '.(int) ($platformSettings['default_portfolio_quota_mb'] ?? 500).' MB',
                            'Client project files default: '.(int) ($platformSettings['client_project_quota_mb'] ?? 2048).' MB per project',
                        ],
                    ],
                ],
            ],
            'resources' => [
                'title' => 'Creator Resources',
                'lede' => 'Tools for running a calmer studio.',
                'hero' => '/branding/categories/reels.jpg',
                'image' => '/branding/categories/ai_motion.jpg',
                'gallery' => [
                    '/branding/categories/reels.jpg',
                    '/branding/categories/ai_motion.jpg',
                    '/branding/categories/voice.jpg',
                    '/branding/categories/bloggers.jpg',
                    '/branding/hero-egypt-creators.jpg',
                ],
                'html' => '<p>Use the vendor console for availability, portfolio, payouts, disputes and messages. The Lens app is the fastest way to reply on the day of the shoot.</p>',
                'sections' => [
                    [
                        'title' => 'Where to work',
                        'body' => 'Same tools creators use after verification.',
                        'image' => '/branding/categories/reels.jpg',
                        'items' => [
                            'Vendor console — profile, pricing, portfolio, calendar',
                            'Lens app — chat, call, WhatsApp, and day-of updates',
                            'Help & Support — report booking or payout issues',
                            'Creator Guidelines — profile and delivery standards',
                        ],
                    ],
                    [
                        'title' => 'Grow your bookings',
                        'body' => 'Clients book what they can trust at a glance.',
                        'image' => '/branding/categories/bloggers.jpg',
                        'items' => [
                            'Keep portfolio projects current and tagged by style',
                            'Publish clear packages with inclusions and duration',
                            'Open recurring availability so clients can checkout without DMs',
                            'Answer briefs quickly — response speed ranks you higher in search',
                        ],
                    ],
                    [
                        'title' => 'Money & delivery',
                        'body' => 'Escrow, deliverables, and payouts stay inside Lens.',
                        'image' => '/branding/categories/food_stylist.jpg',
                        'items' => [
                            'Confirm payout methods before your first release',
                            'Upload deliverables when Approve-to-release applies',
                            'Track wallet and released funds from the vendor console',
                            'Escalate blocked payouts via Help & Support → Payment',
                        ],
                    ],
                ],
            ],
        ];
    }
}
