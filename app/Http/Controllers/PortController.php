<?php

namespace App\Http\Controllers;

use App\Services\PortListingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PortController extends Controller
{
    public function index(Request $request, PortListingService $portListingService): View
    {
        $search = trim((string) $request->query('search'));
        $unlocode = strtoupper(trim((string) $request->query('unlocode')));
        $countryCode = (string) $request->query('country_code');
        $page = max(1, (int) $request->integer('page', 1));

        $ports = $portListingService
            ->list([
                'search' => $search,
                'unlocode' => $unlocode,
                'country_code' => $countryCode,
            ], 100, $page)
            ->withQueryString();

        $countries = $portListingService->countries();

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
