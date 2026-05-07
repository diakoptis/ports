<?php

namespace App\Console\Commands;

use App\Jobs\SyncPortsJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

#[Signature('r4s:sync-ports {--search= : Limit sync to ports matching the given search term}')]
#[Description('Dispatch a queued sync for ports from the Risk4Sea API')]
class SyncPorts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $search = $this->option('search');
        $normalizedSearch = is_string($search) ? $search : null;

        Bus::dispatch(new SyncPortsJob($normalizedSearch));

        Log::info('Risk4Sea port sync job dispatched.', [
            'search' => $normalizedSearch,
            'queue' => 'syncs',
        ]);

        $this->newLine();
        $this->info('Risk4Sea sync job dispatched successfully.');
        $this->line('Queue: syncs');
        $this->line('Run a worker with: php artisan queue:work --queue=syncs');

        return self::SUCCESS;
    }
}
