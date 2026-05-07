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
            ->selectListColumns()
            ->searchByName($search)
            ->filterByUnlocode($unlocode)
            ->filterByCountryCode($countryCode)
            ->orderForListing()
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
