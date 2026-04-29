<?php

namespace App\Services;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TeamService
{
    public function generateInvite(array $data): Invite
    {
        return Invite::create([
            'code' => bin2hex(random_bytes(16)),
            'email' => $data['email'] ?? null,
            'role' => $data['role'] ?? 'member',
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function validateInvite(string $code): Invite
    {
        // Must allow search without global scope for unauthenticated users checking invites
        $invite = Invite::withoutGlobalScopes()
            ->where('code', $code)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invite) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired invite code.'],
            ]);
        }

        return $invite;
    }

    public function joinWorkspace(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $invite = $this->validateInvite($data['code']);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'workspace_id' => $invite->workspace_id,
                'role' => $invite->role,
            ]);

            $invite->update(['status' => 'accepted']);

            $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token
            ];
        });
    }
}
