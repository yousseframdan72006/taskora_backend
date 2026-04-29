<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InviteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'workspace_id' => $this->workspace_id,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
        ];
    }
}
