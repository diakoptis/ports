<?php

use App\Models\Port;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('api returns paginated ports filtered by country code', function () {
    Port::factory()->create([
        'unlocode' => 'ESLCG',
        'name' => 'A Coruna',
        'country_name' => 'Spain',
        'country_code' => 'ESP',
    ]);

    Port::factory()->create([
        'unlocode' => 'GRPIR',
        'name' => 'Piraeus',
        'country_name' => 'Greece',
        'country_code' => 'GRC',
    ]);

    $response = $this->getJson('/api/ports?country_code=ESP');

    $response->assertSuccessful();
    $response->assertJsonPath('data.0.unlocode', 'ESLCG');
    $response->assertJsonPath('data.0.name', 'A Coruna');
    $response->assertJsonPath('data.0.country.code', 'ESP');
    $response->assertJsonCount(1, 'data');
    $response->assertJsonStructure([
        'data',
        'links',
        'meta',
    ]);
});

test('api returns only matching port name search results', function () {
    Port::factory()->create([
        'unlocode' => 'ESLCG',
        'name' => 'A Coruna',
        'country_name' => 'Spain',
        'country_code' => 'ESP',
    ]);

    Port::factory()->create([
        'unlocode' => 'USNYC',
        'name' => 'New York',
        'country_name' => 'United States',
        'country_code' => 'USA',
    ]);

    $response = $this->getJson('/api/ports?search=Coruna');

    $response->assertSuccessful();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.unlocode', 'ESLCG');
});
