<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateInviteRequest;
use App\Http\Requests\JoinWorkspaceRequest;
use App\Http\Resources\InviteResource;
use App\Http\Resources\UserResource;
use App\Services\TeamService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    use ApiResponse;

    protected $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    public function generateInvite(GenerateInviteRequest $request)
    {
        if (!$request->user()->isAdmin()) {
            return $this->error('Unauthorized', 403);
        }
        
        $invite = $this->teamService->generateInvite($request->validated());
        return $this->success(new InviteResource($invite), 'Invite generated.', 201);
    }

    public function validateInvite(Request $request, $code)
    {
        $invite = $this->teamService->validateInvite($code);
        return $this->success(new InviteResource($invite), 'Invite is valid.');
    }

    public function joinWorkspace(JoinWorkspaceRequest $request)
    {
        $data = $this->teamService->joinWorkspace($request->validated());
        return $this->success([
            'user' => new UserResource($data['user']),
            'token' => $data['token']
        ], 'Joined workspace successfully.', 201);
    }
}
