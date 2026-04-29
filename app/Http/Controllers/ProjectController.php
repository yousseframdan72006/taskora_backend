<?php

namespace App\Http\Controllers;

use App\Events\MemberRemovedFromProject;
use App\Events\ProjectCreated;
use App\Events\ProjectDeleted;
use App\Events\ProjectUpdated;
use App\Http\Requests\AssignUserRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('users');

        if ($request->user()->role !== 'admin') {
            $query->whereHas('users', fn($q) => $q->where('users.id', $request->user()->id));
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
            'message' => 'Projects retrieved successfully.'
        ]);
    }

    public function store(StoreProjectRequest $request)
    {
        Gate::authorize('create', Project::class);
        $validated = $request->validated();
        $validated['status'] = 'active';
        $project = Project::create($validated);

        // Auto-assign the creator as member (project admin removed)
        $project->users()->attach($request->user()->id, ['role' => 'member']);

        // Notify all assigned members (excludes the creator automatically)
        ProjectCreated::dispatch($project, $request->user()->id);

        \App\Services\ActivityLogger::log($project->workspace_id, "{$request->user()->name} created a new project '{$project->name}'.", $project->id);

        return response()->json([
            'success' => true,
            'data'    => $project->load('users'),
            'message' => 'Project created successfully.',
        ], 201);
    }

    public function show(Request $request, Project $project)
    {
        $project->load('users');
        $project->load(['tasks' => function ($query) use ($request) {
            if ($request->user()->role !== 'admin') {
                $query->whereHas('users', fn($q) => $q->where('users.id', $request->user()->id));
            }
        }]);

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Project retrieved successfully.'
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        if ($request->has('status') && $request->status === 'completed') {
            \App\Models\Activity::where('project_id', $project->id)->delete();
        }

        // Notify all project members of the update
        ProjectUpdated::dispatch($project, $request->user()->id);

        \App\Services\ActivityLogger::log($project->workspace_id, "{$request->user()->name} updated project '{$project->name}'.", $project->id);

        return response()->json([
            'success' => true,
            'data' => $project,
            'message' => 'Project updated successfully.'
        ]);
    }

    public function destroy(Request $request, Project $project)
    {
        Gate::authorize('delete', $project);

        // Capture data BEFORE deletion
        $projectName = $project->name;
        $workspaceId = $project->workspace_id;
        $memberIds   = $project->load('users')->users->pluck('id')->toArray();

        // مسح جميع الأحداث السريعة المرتبطة بهذا المشروع قبل حذفه
        \App\Models\Activity::where('project_id', $project->id)->delete();

        $project->delete();

        // Notify all former members
        ProjectDeleted::dispatch($projectName, $workspaceId, $memberIds, $request->user()->id);

        \App\Services\ActivityLogger::log($workspaceId, "{$request->user()->name} deleted project '{$projectName}'.");

        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Project deleted successfully.'
        ]);
    }

    public function assignMembers(AssignUserRequest $request, Project $project)
    {
        try {
            Gate::authorize('update', $project);

            $role = $request->role ?? 'member';
            foreach ($request->user_ids as $id) {
                $project->users()->syncWithoutDetaching([$id => ['role' => $role]]);
                // Ensure the role is updated even if the user was already attached
                $project->users()->updateExistingPivot($id, ['role' => $role]);
            }

            \App\Jobs\SendPushNotification::dispatch(
                userIds:       $request->user_ids,
                title:         '📁 مشروع جديد',
                body:          "تم إضافتك إلى مشروع: {$project->name}",
                data:          [
                    'type'       => 'project_assigned', // or workspace_invite depending on context
                    'project_id' => $project->id,
                    'role'       => $request->role ?? 'member',
                ],
                excludeUserId: $request->user()->id,
            );

            $count = count($request->user_ids);
            \App\Services\ActivityLogger::log($project->workspace_id, "{$request->user()->name} added {$count} members to project '{$project->name}'.", $project->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name
                ],
                'message' => 'Members assigned successfully.'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Assign members failed: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeMember(Request $request, Project $project, $userId)
    {
        Gate::authorize('update', $project);

        if ($request->user()->id === $userId) {
            abort(400, 'You cannot remove yourself.');
        }

        $project->users()->detach($userId);

        $project->tasks()->each(function ($task) use ($userId) {
            $task->users()->detach($userId);
        });

        $removedUser = \App\Models\User::find($userId);
        \App\Services\ActivityLogger::log($project->workspace_id, "{$request->user()->name} removed '{$removedUser->name}' from project '{$project->name}'.", $project->id);

        // Notify the removed user
        MemberRemovedFromProject::dispatch($project, $userId, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Member removed from project successfully.'
        ]);
    }
}
