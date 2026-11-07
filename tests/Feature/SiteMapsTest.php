<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SiteMapsTest extends TestCase
{
    public function test_maps_search_falls_back_to_nominatim(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'REQUEST_DENIED', 'error_message' => 'denied'], 200),
            'nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'display_name' => 'Zamalek, Cairo, Egypt',
                    'lat' => '30.0626',
                    'lon' => '31.2197',
                ],
            ], 200),
        ]);

        $this->getJson(route('site.maps.search', ['q' => 'Zamalek']))
            ->assertOk()
            ->assertJsonPath('results.0.label', 'Zamalek, Cairo, Egypt')
            ->assertJsonPath('results.0.lat', 30.0626)
            ->assertJsonPath('results.0.lng', 31.2197);
    }

    public function test_maps_reverse_falls_back_to_nominatim(): void
    {
        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'REQUEST_DENIED'], 200),
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Tahrir Square, Cairo, Egypt',
            ], 200),
        ]);

        $this->getJson(route('site.maps.reverse', ['lat' => 30.0444, 'lng' => 31.2357]))
            ->assertOk()
            ->assertJsonPath('label', 'Tahrir Square, Cairo, Egypt');
    }
}
