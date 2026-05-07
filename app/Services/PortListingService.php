<?php

namespace App\Services;

use App\Models\Port;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PortListingService
{
    protected const COUNTRIES_CACHE_KEY = 'ports:countries';

    public function list(array $filters, int $perPage = 100, int $page = 1): LengthAwarePaginator
    {
        $normalizedFilters = $this->normalizeFilters($filters);
        $cacheKey = $this->listCacheKey($normalizedFilters, $perPage, $page);
        $ttl = now()->addMinutes((int) config('services.risk4sea.list_cache_ttl'));

        /** @var array{items: list<array<string, mixed>>, total: int} $payload */
        $payload = Cache::remember($cacheKey, $ttl, function () use ($normalizedFilters, $perPage, $page): array {
            $query = Port::query()
                ->selectListColumns()
                ->searchByName($normalizedFilters['search'])
                ->filterByUnlocode($normalizedFilters['unlocode'])
                ->filterByCountryCode($normalizedFilters['country_code'])
                ->orderForListing();

            return [
                'items' => $query
                    ->forPage($page, $perPage)
                    ->get()
                    ->toArray(),
                'total' => (clone $query)->count(),
            ];
        });

        return new LengthAwarePaginator(
            Port::hydrate($payload['items']),
            $payload['total'],
            $perPage,
            $page,
            [
                'pageName' => 'page',
            ],
        );
    }

    /**
     * @return Collection<int, Port>
     */
    public function countries(): Collection
    {
        $ttl = now()->addMinutes((int) config('services.risk4sea.list_cache_ttl'));

        /** @var list<array{country_code: string, country_name: string}> $countries */
        $countries = Cache::remember(self::COUNTRIES_CACHE_KEY, $ttl, fn (): array => Port::query()
            ->select(['country_code', 'country_name'])
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->groupBy(['country_code', 'country_name'])
            ->orderBy('country_name')
            ->get()
            ->toArray());

        return collect($countries);
    }

    public function forgetCountriesCache(): void
    {
        Cache::forget(self::COUNTRIES_CACHE_KEY);
    }

    /**
     * @param  array{search?: string|null, unlocode?: string|null, country_code?: string|null}  $filters
     * @return array{search: string, unlocode: string, country_code: string}
     */
    protected function normalizeFilters(array $filters): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'unlocode' => strtoupper(trim((string) ($filters['unlocode'] ?? ''))),
            'country_code' => trim((string) ($filters['country_code'] ?? '')),
        ];
    }

    /**
     * @param  array{search: string, unlocode: string, country_code: string}  $filters
     */
    protected function listCacheKey(array $filters, int $perPage, int $page): string
    {
        return sprintf(
            'ports:list:%s',
            sha1(json_encode([
                'filters' => $filters,
                'per_page' => $perPage,
                'page' => $page,
            ], JSON_THROW_ON_ERROR)),
        );
    }
}
