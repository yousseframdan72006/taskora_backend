<?php

namespace App\Http\Controllers;

use App\Events\MemberRemovedFromWorkspace;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Services\WorkspaceService;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    protected WorkspaceService $workspaceService;

    public function __construct(WorkspaceService $workspaceService)
    {
        $this->workspaceService = $workspaceService;
    }

    public function store(StoreWorkspaceRequest $request)
    {
        try {
            $workspace = $this->workspaceService->create($request->validated(), $request->user());

            return response()->json([
                'success' => true,
                'data' => [
                    'workspace' => $workspace,
                    'user' => $request->user()->refresh(), // Fetch new token / roles if needed on frontend
                ],
                'message' => 'Workspace created successfully.'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function show(Request $request)
    {
        $workspace = $request->user()->workspace->load('users');
        $data = $workspace->toArray();
        $data['members'] = $data['users'];
        unset($data['users']);

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Workspace retrieved successfully.'
        ]);
    }

    public function update(Request $request)
    {
        if (!$request->user()->isWorkspaceOwner()) {
            abort(403, 'Only the workspace owner can update the workspace settings.');
        }

        $request->validate(['name' => 'required|string|max:255']);
        $workspace = $request->user()->workspace;
        $workspace->update($request->only('name'));

        return response()->json([
            'success' => true,
            'data' => $workspace,
            'message' => 'Workspace updated successfully.'
        ]);
    }

    public function members(Request $request)
    {
        $users = $request->user()->workspace->users()->get();
        
        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Workspace members retrieved.'
        ]);
    }

    public function stats(Request $request)
    {
        $workspace = $request->user()->workspace;

        if (!$workspace) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'no_workspace'
            ], 404);
        }

        $user = $request->user();
        $isAdmin = $user->isWorkspaceAdmin();

        $totalProjects = $isAdmin 
            ? $workspace->projects()->count()
            : $workspace->projects()->whereHas('users', fn($q) => $q->where('users.id', $user->id))->count();

        $taskQuery = $isAdmin 
            ? $workspace->tasks()
            : $workspace->tasks()->whereHas('users', fn($q) => $q->where('users.id', $user->id));

        $totalTasks = (clone $taskQuery)->count();
        $pendingTasks = (clone $taskQuery)->where('status', 'pending')->count();
        $inProgressTasks = (clone $taskQuery)->where('status', 'in_progress')->count();
        $doneTasks = (clone $taskQuery)->where('status', 'done')->count();
        $totalMembers = $workspace->users()->count();

        $mostLoadedUser = $workspace->users()->withCount('tasks')->orderByDesc('tasks_count')->first();

        // Calculate unread notifications count
        $unreadNotificationsCount = \App\Models\Notification::where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'role' => $user->role,
                'stats' => [
                    'total_projects' => $totalProjects,
                    'total_tasks' => $totalTasks,
                    'pending_tasks' => $pendingTasks,
                    'in_progress_tasks' => $inProgressTasks,
                    'done_tasks' => $doneTasks,
                    'total_members' => $totalMembers,
                    'unread_notifications_count' => $unreadNotificationsCount,
                ],
                'most_loaded_user' => $mostLoadedUser ? [
                    'id' => $mostLoadedUser->id,
                    'name' => $mostLoadedUser->name,
                    'tasks_count' => $mostLoadedUser->tasks_count,
                ] : null,
                'recent_activities' => $isAdmin 
                    ? \App\Models\Activity::where('workspace_id', $workspace->id)->orderByDesc('created_at')->take(10)->get()
                    : [],
            ],
            'message' => 'Workspace stats retrieved successfully.'
        ]);
    }

    public function removeMember(Request $request, $userId)
    {
        $workspace = $request->user()->workspace;
        
        if (!$request->user()->isWorkspaceAdmin()) {
            abort(403, 'Only admins can remove members.');
        }

        if ($request->user()->id === $userId) {
            abort(400, 'You cannot remove yourself.');
        }

        $removedUser = \App\Models\User::find($userId);

        if (!$removedUser || $removedUser->workspace_id !== $workspace->id) {
            abort(404, 'User not found in this workspace.');
        }

        if ($removedUser->role === 'owner') {
            abort(403, 'You cannot remove the workspace owner.');
        }

        if ($request->user()->role === 'admin' && in_array($removedUser->role, ['admin', 'owner'])) {
            abort(403, 'Admins cannot remove other admins or the owner.');
        }

        $workspace->projects()->each(function ($project) use ($userId) {
            $project->users()->detach($userId);
        });

        $workspace->tasks()->each(function ($task) use ($userId) {
            $task->users()->detach($userId);
        });

        $removedUser->update(['workspace_id' => null, 'role' => 'member']); // Reset role to member upon leaving

        \App\Services\ActivityLogger::log($workspace->id, "{$request->user()->name} removed '{$removedUser->name}' from the workspace.");

        // Notify the removed user
        MemberRemovedFromWorkspace::dispatch($workspace, $userId, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully from workspace.'
        ]);
    }

    public function updateRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|in:admin,member' // Can only assign admin or member (not owner)
        ]);

        $workspace = $request->user()->workspace;

        if (!$request->user()->isWorkspaceOwner()) {
            abort(403, 'Only the workspace owner can change member roles.');
        }

        $targetUser = \App\Models\User::find($userId);

        if (!$targetUser || $targetUser->workspace_id !== $workspace->id) {
            abort(404, 'User not found in this workspace.');
        }

        if ($targetUser->role === 'owner') {
            abort(403, 'You cannot change the role of the workspace owner.');
        }

        $targetUser->update(['role' => $request->role]);

        \App\Services\ActivityLogger::log($workspace->id, "{$request->user()->name} updated '{$targetUser->name}' role to {$request->role}.");

        \App\Jobs\SendPushNotification::dispatch(
            userIds: [$targetUser->id],
            title: 'تحديث الصلاحيات',
            body: "تم تحديث صلاحياتك في مساحة العمل لتصبح: {$request->role}",
            data: [
                'type' => 'role_updated',
                'workspace_id' => $workspace->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully.'
        ]);
    }

    public function leave(Request $request)
    {
        $workspace = $request->user()->workspace;
        $user = $request->user();

        if ($user->role === 'owner') {
            abort(403, 'The owner cannot leave the workspace. You must delete it or transfer ownership first.');
        }

        $workspace->projects()->each(function ($project) use ($user) {
            $project->users()->detach($user->id);
        });

        $workspace->tasks()->each(function ($task) use ($user) {
            $task->users()->detach($user->id);
        });

        $user->update(['workspace_id' => null, 'role' => 'member']); // Reset role

        \App\Services\ActivityLogger::log($workspace->id, "{$user->name} left the workspace.");

        return response()->json([
            'success' => true,
            'message' => 'You have left the workspace successfully.'
        ]);
    }

    public function activities(Request $request)
    {
        $workspace = $request->user()->workspace;
        
        if (!$request->user()->isWorkspaceAdmin()) {
            abort(403, 'Only admins can view workspace activities.');
        }

        $activities = \App\Models\Activity::where('workspace_id', $workspace->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities,
            'message' => 'Workspace activities retrieved.'
        ]);
    }
}
