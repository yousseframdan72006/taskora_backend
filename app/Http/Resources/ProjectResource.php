<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'workspace_id' => $this->workspace_id,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'created_at' => $this->created_at,
        ];
    }
}
