<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'unlocode' => $this->unlocode,
            'name' => $this->name,
            'country' => [
                'name' => $this->country_name,
                'code' => $this->country_code,
            ],
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
