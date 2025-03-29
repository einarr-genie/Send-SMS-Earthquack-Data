<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Earthquake;
use App\Services\EarthquakeScraperService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EarthquakeController extends Controller
{
    protected EarthquakeScraperService $scraperService;

    public function __construct(EarthquakeScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    /**
     * Get a list of earthquakes with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Earthquake::query();

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('origin_time', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('origin_time', '<=', $request->end_date);
        }

        // Filter by magnitude
        if ($request->has('min_magnitude')) {
            $query->where('magnitude', '>=', $request->min_magnitude);
        }

        if ($request->has('max_magnitude')) {
            $query->where('magnitude', '<=', $request->max_magnitude);
        }

        // Filter by region
        if ($request->has('region')) {
            $query->where('region', 'like', '%' . $request->region . '%')
                  ->orWhere('region_th', 'like', '%' . $request->region . '%');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $earthquakes = $query->orderBy('origin_time', 'desc')->paginate($perPage);

        return response()->json($earthquakes);
    }

    /**
     * Get recent earthquakes (last 24 hours).
     */
    public function recent(): JsonResponse
    {
        $earthquakes = Earthquake::recent()->orderBy('origin_time', 'desc')->get();
        return response()->json($earthquakes);
    }

    /**
     * Get significant earthquakes (magnitude >= 4.0).
     */
    public function significant(): JsonResponse
    {
        $earthquakes = Earthquake::significant()->orderBy('origin_time', 'desc')->get();
        return response()->json($earthquakes);
    }

    /**
     * Get details for a specific earthquake.
     */
    public function show(Earthquake $earthquake): JsonResponse
    {
        return response()->json($earthquake);
    }

    /**
     * Manually trigger data fetching.
     */
    public function fetchData(): JsonResponse
    {
        $count = $this->scraperService->fetchAndSaveData();
        
        return response()->json([
            'success' => true,
            'message' => "Earthquake data updated: {$count} new earthquakes added",
        ]);
    }
}