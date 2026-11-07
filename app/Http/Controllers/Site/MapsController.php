<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MapsController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        if ($query === '' || mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results = $this->googlePlaces($query);
        if ($results === []) {
            $results = $this->nominatimSearch($query);
        }

        return response()->json(['results' => array_slice($results, 0, 6)]);
    }

    public function reverse(Request $request): JsonResponse
    {
        $lat = (float) $request->query('lat');
        $lng = (float) $request->query('lng');
        if (! $this->validCoords($lat, $lng)) {
            return response()->json(['label' => null], 422);
        }

        $label = $this->googleReverse($lat, $lng) ?? $this->nominatimReverse($lat, $lng);

        return response()->json([
            'label' => $label ?: sprintf('%.5f, %.5f', $lat, $lng),
            'lat' => $lat,
            'lng' => $lng,
        ]);
    }

    private function mapsKey(): string
    {
        return trim((string) config('services.google.maps_key', ''));
    }

    private function validCoords(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    /**
     * @return list<array{label: string, lat: float, lng: float}>
     */
    private function googlePlaces(string $query): array
    {
        $key = $this->mapsKey();
        if ($key === '') {
            return [];
        }

        try {
            $auto = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/autocomplete/json', [
                'input' => $query,
                'components' => 'country:eg',
                'key' => $key,
            ])->json();
        } catch (\Throwable) {
            return [];
        }

        if (($auto['status'] ?? '') !== 'OK' || ! is_array($auto['predictions'] ?? null)) {
            return [];
        }

        $out = [];
        foreach (array_slice($auto['predictions'], 0, 5) as $prediction) {
            $placeId = (string) ($prediction['place_id'] ?? '');
            if ($placeId === '') {
                continue;
            }
            try {
                $details = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/place/details/json', [
                    'place_id' => $placeId,
                    'fields' => 'formatted_address,geometry,name',
                    'key' => $key,
                ])->json();
            } catch (\Throwable) {
                continue;
            }
            if (($details['status'] ?? '') !== 'OK') {
                continue;
            }
            $result = $details['result'] ?? [];
            $loc = $result['geometry']['location'] ?? null;
            if (! is_array($loc)) {
                continue;
            }
            $label = (string) ($result['formatted_address'] ?? $result['name'] ?? $prediction['description'] ?? '');
            if ($label === '') {
                continue;
            }
            $out[] = [
                'label' => $label,
                'lat' => (float) $loc['lat'],
                'lng' => (float) $loc['lng'],
            ];
        }

        return $out;
    }

    private function googleReverse(float $lat, float $lng): ?string
    {
        $key = $this->mapsKey();
        if ($key === '') {
            return null;
        }

        try {
            $data = Http::timeout(8)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'latlng' => $lat.','.$lng,
                'key' => $key,
            ])->json();
        } catch (\Throwable) {
            return null;
        }

        if (($data['status'] ?? '') !== 'OK') {
            return null;
        }

        $label = $data['results'][0]['formatted_address'] ?? null;

        return is_string($label) && $label !== '' ? $label : null;
    }

    /**
     * @return list<array{label: string, lat: float, lng: float}>
     */
    private function nominatimSearch(string $query): array
    {
        try {
            $rows = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'LensMaps/1.0 (https://lensapp.org)',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'jsonv2',
                    'limit' => 6,
                    'countrycodes' => 'eg',
                    'q' => $query,
                ])
                ->json();
        } catch (\Throwable) {
            return [];
        }

        if (! is_array($rows)) {
            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $label = (string) ($row['display_name'] ?? '');
            $lat = isset($row['lat']) ? (float) $row['lat'] : 0.0;
            $lng = isset($row['lon']) ? (float) $row['lon'] : 0.0;
            if ($label === '' || ! $this->validCoords($lat, $lng)) {
                continue;
            }
            $out[] = ['label' => $label, 'lat' => $lat, 'lng' => $lng];
        }

        return $out;
    }

    private function nominatimReverse(float $lat, float $lng): ?string
    {
        try {
            $data = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'LensMaps/1.0 (https://lensapp.org)',
                ])
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lng,
                    'accept-language' => 'en',
                ])
                ->json();
        } catch (\Throwable) {
            return null;
        }

        $label = $data['display_name'] ?? null;

        return is_string($label) && $label !== '' ? $label : null;
    }
}
