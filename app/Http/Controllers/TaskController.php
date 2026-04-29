<?php

namespace App\Http\Controllers;

use App\Events\TaskAssigned;
use App\Events\TaskChanged;
use App\Events\TaskDeleted;
use App\Events\TaskStatusChanged;
use App\Events\TaskUpdated;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['project', 'users']);

        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Member isolation: only see tasks assigned to them
        if ($request->user()->role !== 'admin') {
            $query->whereHas('users', fn($q) => $q->where('users.id', $request->user()->id));
        }

        return response()->json([
            'success' => true,
            'data'    => $query->get(),
            'message' => 'Tasks retrieved successfully.',
        ]);
    }

    public function store(StoreTaskRequest $request)
    {
        Gate::authorize('create', Task::class);
        
        $validated = $request->validated();
        $assignees = $validated['assignees'] ?? [];
        unset($validated['assignees']); // Remove assignees before DB insert
        
        $task = Task::create($validated);
        
        // Auto-assign the creator if they are not already in assignees
        if (!in_array($request->user()->id, $assignees)) {
            $task->users()->attach($request->user()->id, ['role' => 'member']);
        }

        $task->project->updateStatus();

        if (!empty($assignees)) {
            $syncData = [];
            foreach ($assignees as $userId) {
                $syncData[$userId] = [
                    'role' => 'member',
                ];
                if (!$task->project->users()->where('users.id', $userId)->exists()) {
                    $task->project->users()->attach($userId, [
                        'role' => 'member',
                    ]);
                }
            }
            $task->users()->syncWithoutDetaching($syncData);

            // Dispatch TaskAssigned for each new assignee
            foreach ($assignees as $userId) {
                TaskAssigned::dispatch(
                    $task,
                    $userId,
                    $request->user()->id,
                    'member'
                );
            }
        }

        \App\Services\ActivityLogger::log($task->workspace_id, "{$request->user()->name} created a new task '{$task->title}'.", $task->project_id);

        return response()->json([
            'success' => true,
            'data'    => $task->load('users'),
            'message' => 'Task created successfully.',
        ], 201);
    }

    public function show(Request $request, Task $task)
    {
        if ($request->user()->role !== 'admin' && !$task->users()->where('users.id', $request->user()->id)->exists()) {
            abort(403, 'Unauthorized access to this task.');
        }

        return response()->json([
            'success' => true,
            'data'    => $task->load(['project', 'users', 'comments.user']),
            'message' => 'Task retrieved successfully.',
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        if ($request->user()->role === 'admin') {
            Gate::authorize('update', $task);
        } else {
            Gate::authorize('updateStatus', $task);
            // Member can ONLY update status or reports
            if ($request->anyFilled(['title', 'description', 'priority', 'due_date'])) {
                abort(403, 'Members can only update task status and reports.');
            }
        }

        $oldStatus = $task->status;
        $task->update($request->validated());

        if ($request->has('status')) {
            $task->project->updateStatus();
        }

        if ($request->has('status') && $request->status !== $oldStatus) {
            // Specific event for status changes → triggers notification + audit
            TaskStatusChanged::dispatch(
                $task,
                $oldStatus,
                $task->status,
                $request->user()->id
            );
            \App\Services\ActivityLogger::log($task->workspace_id, "{$request->user()->name} changed task '{$task->title}' status to '{$task->status}'.", $task->project_id);
        } elseif ($request->anyFilled(['title', 'description', 'priority', 'due_date', 'report_text', 'report_file_url'])) {
            // General update → audit trail + push notification to all participants
            $changedFields = array_keys($request->only(['title', 'description', 'priority', 'due_date', 'report_text']));
            TaskChanged::dispatch($task, 'updated', null, null, $request->user()->id);
            TaskUpdated::dispatch($task, $request->user()->id, $changedFields);
            \App\Services\ActivityLogger::log($task->workspace_id, "{$request->user()->name} updated task '{$task->title}'.", $task->project_id);
        }

        return response()->json([
            'success' => true,
            'data'    => $task,
            'message' => 'Task updated successfully.',
        ]);
    }

    public function assign(Request $request, Task $task)
    {
        $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'role'    => 'required|in:assignee,reviewer',
        ]);

        Gate::authorize('update', $task);

        $task->users()->syncWithoutDetaching([
            $request->user_id => [
                'role' => $request->role,
            ],
        ]);

        // Auto-assign to project if not already assigned
        if (!$task->project->users()->where('users.id', $request->user_id)->exists()) {
            $task->project->users()->attach($request->user_id, [
                'role' => 'member',
            ]);
        }

        // Specific event for assignment → triggers notification + audit
        TaskAssigned::dispatch(
            $task,
            $request->user_id,
            $request->user()->id,
            $request->role
        );

        $assignedUser = \App\Models\User::find($request->user_id);
        \App\Services\ActivityLogger::log($task->workspace_id, "{$request->user()->name} assigned '{$assignedUser->name}' to task '{$task->title}'.", $task->project_id);

        return response()->json([
            'success' => true,
            'data'    => $task->load('users'),
            'message' => 'User assigned to task successfully.',
        ]);
    }

    public function destroy(\Illuminate\Http\Request $request, Task $task)
    {
        Gate::authorize('delete', $task);

        // Capture data BEFORE deletion
        $taskTitle      = $task->title;
        $projectId      = $task->project_id;
        $workspaceId    = $task->workspace_id;
        $participantIds = $task->load('users')->users->pluck('id')->toArray();

        $task->delete();

        $project = \App\Models\Project::find($projectId);
        if ($project) {
            $project->updateStatus();
        }

        // Notify all former participants
        TaskDeleted::dispatch($taskTitle, $projectId, $workspaceId, $participantIds, $request->user()->id);

        \App\Services\ActivityLogger::log($workspaceId, "{$request->user()->name} deleted task '{$taskTitle}'.", $projectId);

        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'Task deleted successfully.',
        ]);
    }

    public function removeAssignee(Request $request, Task $task, $userId)
    {
        Gate::authorize('update', $task);

        $task->users()->detach($userId);

        return response()->json([
            'success' => true,
            'message' => 'User removed from task successfully.'
        ]);
    }
}
