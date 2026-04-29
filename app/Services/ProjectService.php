<?php

namespace App\Services;

use App\Models\Project;

class ProjectService
{
    public function list()
    {
        return Project::with('users')->paginate(15);
    }

    public function create(array $data)
    {
        $project = Project::create($data);
        if (!empty($data['assignee_ids'])) {
            $project->users()->sync($data['assignee_ids']);
        }
        return $project;
    }

    public function show(Project $project)
    {
        return $project->load('users', 'tasks');
    }

    public function update(Project $project, array $data)
    {
        $project->update($data);
        return $project;
    }

    public function delete(Project $project)
    {
        $project->delete();
    }

    public function assignMembers(Project $project, array $userIds)
    {
        $project->users()->sync($userIds);
        return $project->load('users');
    }
}
