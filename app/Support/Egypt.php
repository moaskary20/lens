<?php

namespace App\Support;

class Egypt
{
    /**
     * Official 27 Egyptian governorates.
     *
     * @return list<array{name_ar: string, name_en: string, country: string, governorate: string, latitude: float, longitude: float}>
     */
    public static function governorates(): array
    {
        $rows = [
            ['القاهرة', 'Cairo', 30.0444, 31.2357],
            ['الجيزة', 'Giza', 30.0131, 31.2089],
            ['الإسكندرية', 'Alexandria', 31.2001, 29.9187],
            ['القليوبية', 'Qalyubia', 30.4667, 31.1833],
            ['الدقهلية', 'Dakahlia', 31.0409, 31.3785],
            ['الشرقية', 'Sharqia', 30.5877, 31.5020],
            ['الغربية', 'Gharbia', 30.7865, 31.0004],
            ['المنوفية', 'Monufia', 30.5581, 31.0089],
            ['البحيرة', 'Beheira', 31.0333, 30.4667],
            ['كفر الشيخ', 'Kafr El Sheikh', 31.1117, 30.9394],
            ['دمياط', 'Damietta', 31.4165, 31.8133],
            ['بورسعيد', 'Port Said', 31.2653, 32.3019],
            ['الإسماعيلية', 'Ismailia', 30.5965, 32.2715],
            ['السويس', 'Suez', 29.9668, 32.5498],
            ['شمال سيناء', 'North Sinai', 31.1316, 33.8006],
            ['جنوب سيناء', 'South Sinai', 28.2416, 33.6222],
            ['الفيوم', 'Fayoum', 29.3084, 30.8428],
            ['بني سويف', 'Beni Suef', 29.0744, 31.0978],
            ['المنيا', 'Minya', 28.1099, 30.7503],
            ['أسيوط', 'Assiut', 27.1809, 31.1837],
            ['سوهاج', 'Sohag', 26.5590, 31.6957],
            ['قنا', 'Qena', 26.1551, 32.7160],
            ['الأقصر', 'Luxor', 25.6872, 32.6396],
            ['أسوان', 'Aswan', 24.0889, 32.8998],
            ['البحر الأحمر', 'Red Sea', 27.2574, 33.8129],
            ['الوادي الجديد', 'New Valley', 25.4444, 30.5461],
            ['مطروح', 'Matrouh', 31.3543, 27.2373],
        ];

        return array_map(fn (array $row): array => [
            'name_ar' => $row[0],
            'name_en' => $row[1],
            'country' => 'EG',
            'governorate' => $row[1],
            'latitude' => $row[2],
            'longitude' => $row[3],
        ], $rows);
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(self::governorates(), 'name_en');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::governorates() as $governorate) {
            $options[$governorate['name_en']] = $governorate['name_en'].' — '.$governorate['name_ar'];
        }

        return $options;
    }
}
