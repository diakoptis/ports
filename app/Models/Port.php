<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}
