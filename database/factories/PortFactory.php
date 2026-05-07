<?php

namespace Database\Factories;

use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Port>
 */
class PortFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unlocode' => Str::upper(fake()->unique()->bothify('??###')),
            'name' => fake()->city().' Port',
            'country_name' => fake()->country(),
            'country_code' => Str::upper(fake()->lexify('??')),
            'updated_at' => now(),
        ];
    }
}
