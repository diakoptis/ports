<?php

use App\Jobs\SyncPortsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('sync ports command dispatches queue job with search option', function () {
    Bus::fake();

    $this->artisan('r4s:sync-ports', [
        '--search' => 'span',
    ])->assertSuccessful();

    Bus::assertDispatched(SyncPortsJob::class, function (SyncPortsJob $job): bool {
        return $job->search === 'span';
    });
});
