<?php

namespace App\Console\Commands;

use App\Services\PortSyncService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('r4s:sync-ports {--search= : Limit sync to ports matching the given search term}')]
#[Description('Sync ports from the Risk4Sea API into the local database')]
class SyncPorts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PortSyncService $portSyncService): int
    {
        $search = $this->option('search');

        $this->info('Fetching ports from Risk4Sea...');

        try {
            $result = $portSyncService->sync(
                is_string($search) ? $search : null,
            );
        } catch (Throwable $exception) {
            Log::error('Risk4Sea port sync failed during fetch.', [
                'search' => $search,
                'message' => $exception->getMessage(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($result['synced'] === 0) {
            $this->warn('No valid ports were returned by the API.');

            Log::warning('Risk4Sea port sync finished with no valid records.', [
                'search' => $search,
                ...$result,
            ]);

            return self::SUCCESS;
        }

        Log::info('Risk4Sea port sync completed.', [
            'search' => $search,
            ...$result,
        ]);

        $this->newLine();
        $this->info('Risk4Sea sync completed successfully.');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Fetched', $result['fetched']],
                ['Synced', $result['synced']],
                ['New', $result['new']],
                ['Updated', $result['updated']],
                ['Invalid', $result['invalid']],
                ['Duplicate in payload', $result['duplicate_in_payload']],
                ['Total skipped', $result['skipped']],
            ],
        );

        return self::SUCCESS;
    }
}
