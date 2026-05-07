<?php

namespace Database\Seeders;

use App\Models\Port;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PortSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $targetCount = 10000;
        $existingUnlocodes = array_fill_keys(
            Port::query()->pluck('unlocode')->all(),
            true,
        );
        $existingCount = count($existingUnlocodes);
        $missingCount = max(0, $targetCount - $existingCount);

        if ($missingCount === 0) {
            $this->command?->info("Ports table already contains {$existingCount} rows.");

            return;
        }

        $nextFakeIndex = 1;

        collect(range(1, $missingCount))
            ->map(function () use (&$nextFakeIndex, &$existingUnlocodes): array {
                $unlocode = $this->generateFakeUnlocode($nextFakeIndex, $existingUnlocodes);

                return [
                    'unlocode' => $unlocode,
                    'name' => fake()->city().' Port',
                    'country_name' => fake()->country(),
                    'country_code' => Str::upper(fake()->lexify('??')),
                    'updated_at' => now(),
                ];
            })
            ->chunk(500)
            ->each(fn ($chunk) => Port::query()->insert($chunk->all()));

        $this->command?->info("Added {$missingCount} fake ports to reach {$targetCount} total rows.");
    }

    /**
     * @param  array<string, true>  $existingUnlocodes
     */
    protected function generateFakeUnlocode(int &$nextFakeIndex, array &$existingUnlocodes): string
    {
        do {
            $candidate = sprintf('FP%06d', $nextFakeIndex);
            $nextFakeIndex++;
        } while (isset($existingUnlocodes[$candidate]));

        $existingUnlocodes[$candidate] = true;

        return $candidate;
    }
}
