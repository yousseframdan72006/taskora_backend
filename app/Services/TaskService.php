<?php

namespace App\Services;

use App\Models\Task;

class TaskService
{
    public function list(array $filters)
    {
        $query = Task::with('users', 'project');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['assignee_id'])) {
            $query->whereHas('users', function ($q) use ($filters) {
                $q->where('users.id', $filters['assignee_id']);
            });
        }

        return $query->paginate(15);
    }

    public function create(array $data)
    {
        $task = Task::create($data);
        if (!empty($data['assignee_ids'])) {
            $task->users()->sync($data['assignee_ids']);
        }
        return $task->load('users', 'project');
    }

    public function show(Task $task)
    {
        return $task->load('users', 'project', 'comments.user');
    }

    public function update(Task $task, array $data)
    {
        $task->update($data);
        if (isset($data['assignee_ids']) && request()->user()->isAdmin()) {
            $task->users()->sync($data['assignee_ids']);
        }
        return $task->load('users');
    }

    public function updateStatus(Task $task, string $status)
    {
        $task->update(['status' => $status]);
        return $task;
    }

    public function delete(Task $task)
    {
        $task->delete();
    }
}
