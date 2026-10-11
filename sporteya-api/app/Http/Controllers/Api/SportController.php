<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SportController extends Controller
{
    /**
     * Get all active sports (cached 24h in Redis).
     */
    public function index(): JsonResponse
    {
        $sports = Cache::remember('sports:all', 86400, function () {
            return Sport::where('is_active', true)->orderBy('name_ar')->get();
        });

        return response()->json(['sports' => $sports], 200);
    }
}
