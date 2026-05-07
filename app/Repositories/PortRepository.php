<?php

namespace App\Repositories;

use App\Models\Port;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PortRepository
{
    /**
     * @param  Collection<int, array{
     *     unlocode: string,
     *     name: string,
     *     country_name: string,
     *     country_code: string
     * }>  $ports
     * @return Collection<int, string>
     */
    public function existingUnlocodes(Collection $ports): Collection
    {
        return Port::query()
            ->whereIn('unlocode', $ports->pluck('unlocode'))
            ->pluck('unlocode');
    }

    /**
     * @param  Collection<int, array{
     *     unlocode: string,
     *     name: string,
     *     country_name: string,
     *     country_code: string
     * }>  $ports
     */
    public function upsert(Collection $ports, Carbon $timestamp): void
    {
        Port::query()->upsert(
            $ports
                ->map(fn (array $port): array => [
                    ...$port,
                    'updated_at' => $timestamp,
                ])
                ->all(),
            ['unlocode'],
            ['name', 'country_name', 'country_code', 'updated_at'],
        );
    }
}
