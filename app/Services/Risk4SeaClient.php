<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Risk4SeaClient
{
    /**
     * Retrieve and normalize ports from the Risk4Sea API.
     *
     * @return list<array{
     *     unlocode: string,
     *     name: string,
     *     country_name: string,
     *     country_code: string
     * }>
     */
    public function listPorts(?string $search = null): array
    {
        $response = $this->request()
            ->get(
                config('services.risk4sea.ports_path'),
                array_filter([
                    'search' => filled($search) ? trim($search) : null,
                ], fn (mixed $value): bool => $value !== null),
            );

        if ($response->unauthorized()) {
            throw new RuntimeException('Risk4Sea authentication failed. Check the configured API token.');
        }

        $response->throw();

        $ports = $response->json();

        if (! is_array($ports)) {
            throw new RuntimeException('Unexpected Risk4Sea response payload.');
        }

        return array_values(array_map(
            fn (array $port): array => $this->normalizePort($port),
            array_filter($ports, fn (mixed $port): bool => is_array($port)),
        ));
    }

    protected function request(): PendingRequest
    {
        $token = (string) config('services.risk4sea.token');

        if ($token === '') {
            throw new RuntimeException('Risk4Sea token is missing. Set RISK4SEA_TOKEN in the environment.');
        }

        return Http::baseUrl((string) config('services.risk4sea.base_url'))
            ->acceptJson()
            ->asJson()
            ->withToken($token)
            ->timeout((int) config('services.risk4sea.timeout'))
            ->connectTimeout((int) config('services.risk4sea.connect_timeout'));
    }

    /**
     * @param  array<string, mixed>  $port
     * @return array{
     *     unlocode: string,
     *     name: string,
     *     country_name: string,
     *     country_code: string
     * }
     */
    protected function normalizePort(array $port): array
    {
        return [
            'unlocode' => trim((string) Arr::get($port, 'unlocode', '')),
            'name' => trim((string) Arr::get($port, 'name', '')),
            'country_name' => trim((string) Arr::get($port, 'country.name', '')),
            'country_code' => trim((string) Arr::get($port, 'country.code', '')),
        ];
    }
}
