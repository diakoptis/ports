<?php

namespace App\Services;

use App\Data\PortData;
use App\Repositories\PortRepository;

class PortSyncService
{
    public function __construct(
        protected Risk4SeaClient $risk4SeaClient,
        protected PortRepository $portRepository,
        protected PortListingService $portListingService,
    ) {}

    /**
     * @return array{
     *     fetched: int,
     *     synced: int,
     *     new: int,
     *     updated: int,
     *     invalid: int,
     *     duplicate_in_payload: int,
     *     skipped: int
     * }
     */
    public function sync(?string $search = null): array
    {
        $ports = collect($this->risk4SeaClient->listPorts($search))
            ->map(fn (array $port): PortData => PortData::fromRisk4SeaPayload($port));

        $invalidCount = $ports->reject->isValid()->count();

        $preparedPorts = $ports
            ->filter->isValid()
            ->map(fn (PortData $port): array => $port->toArray())
            ->keyBy('unlocode');

        $duplicateInPayloadCount = $ports->filter->isValid()->count() - $preparedPorts->count();
        $skippedCount = $invalidCount + $duplicateInPayloadCount;

        if ($preparedPorts->isEmpty()) {
            return [
                'fetched' => $ports->count(),
                'synced' => 0,
                'new' => 0,
                'updated' => 0,
                'invalid' => $invalidCount,
                'duplicate_in_payload' => $duplicateInPayloadCount,
                'skipped' => $skippedCount,
            ];
        }

        $existingUnlocodes = $this->portRepository->existingUnlocodes($preparedPorts->values());
        $newCount = $preparedPorts->keys()->diff($existingUnlocodes)->count();
        $updatedCount = $preparedPorts->count() - $newCount;

        $this->portRepository->upsert($preparedPorts->values(), now());
        $this->portListingService->forgetCountriesCache();

        return [
            'fetched' => $ports->count(),
            'synced' => $preparedPorts->count(),
            'new' => $newCount,
            'updated' => $updatedCount,
            'invalid' => $invalidCount,
            'duplicate_in_payload' => $duplicateInPayloadCount,
            'skipped' => $skippedCount,
        ];
    }
}
