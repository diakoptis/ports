<?php

namespace App\Services;

use App\Exceptions\Risk4SeaException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        $search = filled($search) ? trim($search) : null;
        $path = (string) config('services.risk4sea.ports_path');
        $context = [
            'search' => $search,
            'path' => $path,
        ];

        try {
            $response = $this->request($context)
                ->get(
                    $path,
                    array_filter([
                        'search' => $search,
                    ], fn (mixed $value): bool => $value !== null),
                );
        } catch (ConnectionException $exception) {
            Log::error('Risk4Sea request failed after retries due to connection issue.', [
                ...$context,
                'message' => $exception->getMessage(),
            ]);

            throw Risk4SeaException::requestFailed('Connection to the Risk4Sea API could not be established.');
        } catch (Throwable $exception) {
            Log::error('Risk4Sea request failed unexpectedly.', [
                ...$context,
                'message' => $exception->getMessage(),
            ]);

            throw Risk4SeaException::requestFailed($exception->getMessage());
        }

        if ($response->unauthorized()) {
            Log::error('Risk4Sea authentication failed.', $context);

            throw Risk4SeaException::authenticationFailed();
        }

        if ($response->failed()) {
            Log::error('Risk4Sea request failed after retries.', [
                ...$context,
                'status' => $response->status(),
            ]);

            throw Risk4SeaException::requestFailed(
                "Received HTTP {$response->status()} from the Risk4Sea API.",
            );
        }

        $ports = $response->json();

        if (! is_array($ports)) {
            throw Risk4SeaException::unexpectedPayload();
        }

        return array_values(array_map(
            fn (array $port): array => $this->normalizePort($port),
            array_filter($ports, fn (mixed $port): bool => is_array($port)),
        ));
    }

    /**
     * @param  array{search: string|null, path: string}  $context
     */
    protected function request(array $context): PendingRequest
    {
        $token = (string) config('services.risk4sea.token');

        if ($token === '') {
            throw Risk4SeaException::missingToken();
        }

        return Http::baseUrl((string) config('services.risk4sea.base_url'))
            ->acceptJson()
            ->asJson()
            ->withToken($token)
            ->retry(
                (int) config('services.risk4sea.retry_times'),
                fn (int $attempt): int => (int) config('services.risk4sea.retry_sleep_ms') * $attempt,
                function (Throwable $exception) use ($context): bool {
                    $shouldRetry = $this->shouldRetry($exception);

                    if ($shouldRetry) {
                        Log::warning('Retrying Risk4Sea API request.', [
                            ...$context,
                            'exception' => $exception::class,
                            'message' => $exception->getMessage(),
                        ]);
                    }

                    return $shouldRetry;
                },
                throw: false,
            )
            ->timeout((int) config('services.risk4sea.timeout'))
            ->connectTimeout((int) config('services.risk4sea.connect_timeout'));
    }

    protected function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            $status = $exception->response?->status();

            return $status === 429 || ($status !== null && $status >= 500);
        }

        return false;
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
