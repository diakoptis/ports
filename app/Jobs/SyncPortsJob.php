<?php

namespace App\Jobs;

use App\Services\PortSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncPortsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [60, 120, 300];

    public int $timeout = 120;

    public function __construct(
        public readonly ?string $search = null,
    ) {}

    public function handle(PortSyncService $portSyncService): void
    {
        Log::info('Risk4Sea port sync job started.', [
            'search' => $this->search,
        ]);

        $result = $portSyncService->sync($this->search);

        if ($result['synced'] === 0) {
            Log::warning('Risk4Sea port sync job finished with no valid records.', [
                'search' => $this->search,
                ...$result,
            ]);

            return;
        }

        Log::info('Risk4Sea port sync job completed successfully.', [
            'search' => $this->search,
            ...$result,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Risk4Sea port sync job failed.', [
            'search' => $this->search,
            'message' => $exception->getMessage(),
        ]);
    }
}
