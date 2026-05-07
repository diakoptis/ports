<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'unlocode',
    'name',
    'country_name',
    'country_code',
])]
class Port extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    public function scopeSelectListColumns(Builder $query): void
    {
        $query->select(['id', 'unlocode', 'name', 'country_name', 'country_code', 'updated_at']);
    }

    public function scopeSearchByName(Builder $query, ?string $search): void
    {
        $query->when(
            filled($search),
            fn (Builder $builder): Builder => $builder->where('name', 'like', '%'.$search.'%'),
        );
    }

    public function scopeFilterByUnlocode(Builder $query, ?string $unlocode): void
    {
        $query->when(
            filled($unlocode),
            fn (Builder $builder): Builder => $builder->where('unlocode', $unlocode),
        );
    }

    public function scopeFilterByCountryCode(Builder $query, ?string $countryCode): void
    {
        $query->when(
            filled($countryCode),
            fn (Builder $builder): Builder => $builder->where('country_code', $countryCode),
        );
    }

    public function scopeOrderForListing(Builder $query): void
    {
        $query->orderBy('name');
    }
}
