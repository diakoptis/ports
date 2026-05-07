<?php

namespace App\Console\Commands;

use App\Models\Port;
use App\Services\Risk4SeaClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('r4s:sync-ports {--search= : Limit sync to ports matching the given search term}')]
#[Description('Sync ports from the Risk4Sea API into the local database')]
class SyncPorts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(Risk4SeaClient $risk4SeaClient): int
    {
        $search = $this->option('search');

        $this->info('Fetching ports from Risk4Sea...');

        try {
            $ports = collect($risk4SeaClient->listPorts(
                is_string($search) ? $search : null,
            ));
        } catch (Throwable $exception) {
            Log::error('Risk4Sea port sync failed during fetch.', [
                'search' => $search,
                'message' => $exception->getMessage(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $invalidPorts = $ports
            ->filter(fn (array $port): bool => $port['unlocode'] === '' || $port['name'] === '');

        $validPorts = $ports
            ->reject(fn (array $port): bool => $port['unlocode'] === '' || $port['name'] === '');

        $preparedPorts = $this->preparePortsForUpsert($validPorts);
        $invalidCount = $invalidPorts->count();
        $duplicateInPayloadCount = $validPorts->count() - $preparedPorts->count();
        $skippedCount = $invalidCount + $duplicateInPayloadCount;

        if ($preparedPorts->isEmpty()) {
            $this->warn('No valid ports were returned by the API.');

            Log::warning('Risk4Sea port sync finished with no valid records.', [
                'search' => $search,
                'fetched' => $ports->count(),
                'invalid' => $invalidCount,
                'duplicate_in_payload' => $duplicateInPayloadCount,
                'skipped' => $skippedCount,
            ]);

            return self::SUCCESS;
        }

        $existingUnlocodes = Port::query()
            ->whereIn('unlocode', $preparedPorts->keys())
            ->pluck('unlocode');

        $newCount = $preparedPorts->keys()->diff($existingUnlocodes)->count();
        $updatedCount = $preparedPorts->count() - $newCount;

        Port::query()->upsert(
            $preparedPorts->values()->all(),
            ['unlocode'],
            ['name', 'country_name', 'country_code', 'updated_at'],
        );

        Log::info('Risk4Sea port sync completed.', [
            'search' => $search,
            'fetched' => $ports->count(),
            'synced' => $preparedPorts->count(),
            'new' => $newCount,
            'updated' => $updatedCount,
            'invalid' => $invalidCount,
            'duplicate_in_payload' => $duplicateInPayloadCount,
            'skipped' => $skippedCount,
        ]);

        $this->newLine();
        $this->info('Risk4Sea sync completed successfully.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Fetched', $ports->count()],
                ['Synced', $preparedPorts->count()],
                ['New', $newCount],
                ['Updated', $updatedCount],
                ['Invalid', $invalidCount],
                ['Duplicate in payload', $duplicateInPayloadCount],
                ['Total skipped', $skippedCount],
            ],
        );

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, array{
     *     unlocode: string,
     *     name: string,
     *     country_name: string,
     *     country_code: string
     * }>  $ports
     * @return Collection<string, array{
     *     unlocode: string,
     *     name: string,
     *     country_name: string,
     *     country_code: string,
     *     updated_at: Carbon
     * }>
     */
    protected function preparePortsForUpsert(Collection $ports): Collection
    {
        $timestamp = now();

        return $ports
            ->map(fn (array $port): array => [
                ...$port,
                'updated_at' => $timestamp,
            ])
            ->keyBy('unlocode');
    }
}
