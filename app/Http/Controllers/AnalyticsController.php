<?php

namespace App\Http\Controllers;

use App\Services\TaskStatsService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AnalyticsController extends Controller
{
    protected TaskStatsService $statsService;

    public function __construct(TaskStatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    public function employeeStats(Request $request)
    {
        Gate::authorize('viewAllTaskStats', $request->user()->workspace);

        $data = $this->statsService->getEmployeesStats($request->user()->workspace_id);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Employees stats retrieved.'
        ]);
    }

    public function userStats(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // Employee can only see their own, Admin can see anyone's in the workspace
        if ($request->user()->id !== $user->id) {
            Gate::authorize('viewAllTaskStats', $request->user()->workspace);
        }

        // Must be in the same workspace to see anything anyway
        if ($user->workspace_id !== $request->user()->workspace_id) {
            abort(403);
        }

        $data = $this->statsService->getUserStats($user);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'User stats retrieved.'
        ]);
    }
}
