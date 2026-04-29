<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get task statistics overview.
     */
    public function taskOverview(Request $request)
    {
        $data = $this->dashboardService->getTaskOverview($request->user());

        return response()->json($data);
    }
}
