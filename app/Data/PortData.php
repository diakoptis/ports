<?php

namespace App\Data;

use Illuminate\Support\Arr;

class PortData
{
    public function __construct(
        public readonly string $unlocode,
        public readonly string $name,
        public readonly string $countryName,
        public readonly string $countryCode,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromRisk4SeaPayload(array $payload): self
    {
        return new self(
            unlocode: trim((string) Arr::get($payload, 'unlocode', '')),
            name: trim((string) Arr::get($payload, 'name', '')),
            countryName: trim((string) Arr::get($payload, 'country.name', '')),
            countryCode: trim((string) Arr::get($payload, 'country.code', '')),
        );
    }

    public function isValid(): bool
    {
        return $this->unlocode !== '' && $this->name !== '';
    }

    /**
     * @return array{
     *     unlocode: string,
     *     name: string,
     *     country_name: string,
     *     country_code: string
     * }
     */
    public function toArray(): array
    {
        return [
            'unlocode' => $this->unlocode,
            'name' => $this->name,
            'country_name' => $this->countryName,
            'country_code' => $this->countryCode,
        ];
    }
}
