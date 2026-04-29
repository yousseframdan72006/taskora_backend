<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'due_date' => $this->due_date,
            'project_id' => $this->project_id,
            'workspace_id' => $this->workspace_id,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'users' => UserResource::collection($this->whenLoaded('users')),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at,
        ];
    }
}
