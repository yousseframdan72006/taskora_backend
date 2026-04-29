<?php

namespace App\Http\Controllers;

use App\Events\InviteResponded;
use App\Events\InviteSent;
use App\Http\Requests\GenerateInviteRequest;
use App\Models\Invite;
use App\Models\Scopes\WorkspaceScope;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InviteController extends Controller
{
    protected InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

 
    public function pending(Request $request)
    {
        $invites = Invite::withoutGlobalScope(WorkspaceScope::class)
            ->with('workspace')
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $invites,
            'message' => 'Pending invites retrieved.'
        ]);
    }

    /**
     * Admin generates an invite.
     */
    public function store(GenerateInviteRequest $request)
    {
        Gate::authorize('inviteUsers', $request->user()->workspace);

        $invite = $this->invitationService->sendInvite(
            $request->validated(),
            $request->user()->workspace_id
        );

        // Dispatch event → sends push notification if invited user has an account
        InviteSent::dispatch($invite);

        return response()->json([
            'success' => true,
            'data'    => [
                'invite' => $invite,
            ],
            'message' => 'Invitation sent successfully.',
        ], 201);
    }

    /**
     * User accepts invite.
     */
    public function accept(Request $request, string $id)
    {
        try {
            $invite = Invite::withoutGlobalScope(WorkspaceScope::class)->findOrFail($id);
            Gate::authorize('respond', $invite);

            $this->invitationService->accept($id, $request->user());

            // Notify the admin that the user accepted
            InviteResponded::dispatch($invite, 'accepted', $request->user()->id, $request->user()->name);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $request->user()->refresh()->load('workspace')
                ],
                'message' => 'Invitation accepted. You have joined the workspace.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * User declines invite.
     */
    public function decline(Request $request, string $id)
    {
        try {
            $invite = Invite::withoutGlobalScope(WorkspaceScope::class)->findOrFail($id);
            Gate::authorize('respond', $invite);

            $this->invitationService->decline($id, $request->user());

            // Notify the admin that the user declined
            InviteResponded::dispatch($invite, 'declined', $request->user()->id, $request->user()->name);

            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Invitation declined.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
