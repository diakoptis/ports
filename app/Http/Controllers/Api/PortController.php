<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortResource;
use App\Models\Port;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class PortController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $search = trim((string) $request->query('search'));
        $unlocode = strtoupper(trim((string) $request->query('unlocode')));
        $countryCode = (string) $request->query('country_code');

        try {
            $ports = Port::query()
                ->selectListColumns()
                ->searchByName($search)
                ->filterByUnlocode($unlocode)
                ->filterByCountryCode($countryCode)
                ->orderForListing()
                ->paginate(100)
                ->withQueryString();
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Unable to load ports.',
                'error' => [
                    'type' => class_basename($exception),
                    'detail' => $exception->getMessage(),
                ],
            ], 500);
        }

        return PortResource::collection($ports);
    }
}
