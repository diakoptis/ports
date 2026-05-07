<?php

use App\Models\Port;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('home page filters ports by exact unlocode', function () {
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

    $response = $this->get('/?unlocode=ESLCG');

    $response->assertSuccessful();
    $response->assertSee('ESLCG');
    $response->assertSee('A Coruna');
    $response->assertDontSee('GRPIR');
    $response->assertDontSee('Piraeus');
});

test('home page filters ports by country code', function () {
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

    $response = $this->get('/?country_code=ESP');

    $response->assertSuccessful();
    $response->assertSee('ESLCG');
    $response->assertSee('A Coruna');
    $response->assertDontSee('USNYC');
    $response->assertDontSee('New York');
});
