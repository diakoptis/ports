<?php

namespace App\Http\Controllers;

use App\Models\Port;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PortController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $unlocode = strtoupper(trim((string) $request->query('unlocode')));
        $countryCode = (string) $request->query('country_code');

        $ports = Port::query()
            ->select(['id', 'unlocode', 'name', 'country_name', 'country_code', 'updated_at'])
            ->when($request->filled('search'), function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('unlocode'), fn($query): mixed => $query->where('unlocode', $unlocode))
            ->when($request->filled('country_code'), fn($query): mixed => $query->where('country_code', $countryCode))
            ->orderBy('name')
            ->paginate(100)
            ->withQueryString();

        $countries = Port::query()
            ->select(['country_code', 'country_name'])
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->groupBy(['country_code', 'country_name'])
            ->orderBy('country_name')
            ->get();




        return view('ports.index', [
            'countries' => $countries,
            'filters' => [
                'search' => $search,
                'unlocode' => $unlocode,
                'country_code' => $countryCode,
            ],
            'ports' => $ports,
        ]);
    }
}
