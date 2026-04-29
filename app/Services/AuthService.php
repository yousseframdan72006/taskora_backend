<?php

namespace App\Services;

use App\Models\Invite;
use App\Models\Scopes\WorkspaceScope;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user.
     */
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->attachPendingInvites($user);

        return [
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ];
    }

    /**
     * Log in existing user.
     */
    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $this->attachPendingInvites($user);

        return [
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ];
    }

    /**
     * Attach existing invites based on email.
     */
    protected function attachPendingInvites(User $user)
    {
        // Must bypass global scope to find invites across all workspaces
        Invite::withoutGlobalScope(WorkspaceScope::class)
            ->where('email', $user->email)
            ->whereNull('user_id')
            ->where('status', 'pending')
            ->update(['user_id' => $user->id]);
    }
}
