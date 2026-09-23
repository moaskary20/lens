<?php

namespace App\Support;

class VendorPhotos
{
    /**
     * @return array<string, list<string>>
     */
    public static function covers(): array
    {
        return [
            'photographer' => [
                'https://images.unsplash.com/photo-1539650116574-75c0c6d73f6e?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1542038784456-1ea8e935640e?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?auto=format&fit=crop&w=1400&h=900&q=80',
            ],
            'videographer' => [
                'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1485846234645-a62644f84789?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1497015289639-546e765f3900?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1601506521937-0121a7fc2a6b?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=1400&h=900&q=80',
            ],
            'reels' => [
                'https://images.unsplash.com/photo-1611162616475-46b635cb6868?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1607748851687-ba9a01946290?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1551817958-20204f6a2f7d?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=1400&h=900&q=80',
            ],
            'studio' => [
                'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=1400&h=900&q=80',
            ],
            'model' => [
                'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1469334031218-e382a71b716b?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?auto=format&fit=crop&w=1400&h=900&q=80',
            ],
            'ugc' => [
                'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1607748851687-ba9a01946290?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1611162616305-c69b3fa7fbe0?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1554177255-61501b91b9d9?auto=format&fit=crop&w=1400&h=900&q=80',
            ],
            'food_stylist' => [
                'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1498837168767-890719321dba?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1400&h=900&q=80',
                'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=1400&h=900&q=80',
            ],
        ];
    }

    /**
     * Unique people portraits so every vendor card shows a real face.
     *
     * @return list<string>
     */
    public static function people(): array
    {
        return [
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1463453091185-61582044d556?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1521119989659-a83eee488004?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1544723795-3fb6469f5b39?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1554151228-14d9def656e4?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1548142813-c348350df52b?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=400&h=400&q=80',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&h=400&q=80',
        ];
    }

    public static function cover(?string $slug, int $seed = 0): string
    {
        return self::pick(self::covers(), $slug, $seed);
    }

    public static function portrait(?string $slug, int $seed = 0): string
    {
        $people = self::people();

        return $people[abs($seed) % count($people)];
    }

    /**
     * @return list<string>
     */
    public static function filters(?string $slug): array
    {
        return match ($slug) {
            'food_stylist' => ['All', 'Food Photography', 'Recipe Videos', 'Brand Campaigns', 'Behind the Scenes'],
            'videographer', 'reels' => ['All', 'Events', 'Commercial', 'Corporate', 'Behind the Scenes'],
            'studio' => ['All', 'Cyclorama', 'Kitchen Set', 'Lifestyle', 'Behind the Scenes'],
            'model' => ['All', 'Editorial', 'Runway', 'Lookbook', 'Behind the Scenes'],
            'ugc' => ['All', 'Social', 'Product', 'Lifestyle', 'Behind the Scenes'],
            default => ['All', 'Weddings', 'Product', 'Portrait', 'Behind the Scenes'],
        };
    }

    /**
     * @return list<array{url: string, title: string, category: string, tall: bool}>
     */
    public static function gallery(?string $slug, int $seed = 0): array
    {
        $urls = array_values(array_unique(array_merge(
            self::extraShots()[$slug] ?? self::extraShots()['photographer'],
            self::covers()[$slug] ?? self::covers()['photographer'],
        )));
        $filters = self::filters($slug);
        $categories = array_values(array_filter($filters, fn (string $item): bool => $item !== 'All'));
        $titles = self::titles($slug);
        $offset = abs($seed) % max(1, count($urls));
        $rotated = array_merge(array_slice($urls, $offset), array_slice($urls, 0, $offset));
        $items = [];

        foreach ($rotated as $index => $url) {
            $items[] = [
                'url' => $url,
                'title' => $titles[$index % count($titles)],
                'category' => $categories[$index % count($categories)],
                'tall' => $index % 3 === 0,
            ];
        }

        return $items;
    }

    /**
     * @return array<string, list<string>>
     */
    protected static function extraShots(): array
    {
        return [
            'photographer' => [
                'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=900&h=1200&q=80',
                'https://images.unsplash.com/photo-1511285560929-80b456fe3ea9?auto=format&fit=crop&w=900&h=700&q=80',
            ],
            'videographer' => [
                'https://images.unsplash.com/photo-1485846234645-a62644f84789?auto=format&fit=crop&w=900&h=700&q=80',
                'https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?auto=format&fit=crop&w=900&h=1200&q=80',
            ],
            'food_stylist' => [
                'https://images.unsplash.com/photo-1565958011703-44f9829ba187?auto=format&fit=crop&w=900&h=700&q=80',
                'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=900&h=700&q=80',
                'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=900&h=700&q=80',
                'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?auto=format&fit=crop&w=900&h=1200&q=80',
                'https://images.unsplash.com/photo-1546833999-b9f581a1996d?auto=format&fit=crop&w=900&h=700&q=80',
                'https://images.unsplash.com/photo-1556910103-1c02745aae4d?auto=format&fit=crop&w=900&h=700&q=80',
            ],
            'studio' => [
                'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&h=1200&q=80',
                'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=900&h=700&q=80',
            ],
            'model' => [
                'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&h=1200&q=80',
                'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=900&h=1200&q=80',
            ],
            'ugc' => [
                'https://images.unsplash.com/photo-1611162616475-46b635cb6868?auto=format&fit=crop&w=900&h=1200&q=80',
                'https://images.unsplash.com/photo-1554177255-61501b91b9d9?auto=format&fit=crop&w=900&h=700&q=80',
            ],
            'reels' => [
                'https://images.unsplash.com/photo-1611162616475-46b635cb6868?auto=format&fit=crop&w=900&h=1200&q=80',
                'https://images.unsplash.com/photo-1551817958-20204f6a2f7d?auto=format&fit=crop&w=900&h=700&q=80',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    protected static function titles(?string $slug): array
    {
        return match ($slug) {
            'food_stylist' => ['Fresh & Real', 'Pasta Perfection', 'BTS On Set', 'Brand Campaign', 'Recipe Frame'],
            'videographer', 'reels' => ['Showreel Cut', 'Event Night', 'Brand Film', 'BTS On Set'],
            'studio' => ['Cyclorama Wall', 'Daylight Corner', 'Kitchen Set', 'BTS On Set'],
            default => ['Editorial Frame', 'Campaign Still', 'Portrait Study', 'BTS On Set'],
        };
    }

    /**
     * @param  array<string, list<string>>  $pool
     */
    protected static function pick(array $pool, ?string $slug, int $seed): string
    {
        $images = $pool[$slug] ?? $pool['photographer'];

        return $images[abs($seed) % count($images)];
    }
}
