<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Register or update a device token for the authenticated user.
     *
     * Called by Flutter on:
     *   - Login
     *   - App open (foreground resume)
     *
     * POST /api/devices/token
     */
    public function register(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string|max:512',
            'platform'     => 'sometimes|string|in:android,ios,web',
        ]);

        // Use updateOrCreate so re-registering an existing token is idempotent.
        // If the token already belongs to THIS user, just update platform.
        // If it belonged to another user (device re-use), re-assign it.
        UserDevice::updateOrCreate(
            ['device_token' => $request->device_token],
            [
                'user_id'  => $request->user()->id,
                'platform' => $request->input('platform', 'android'),
            ]
        );

        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'Device token registered successfully.',
        ]);
    }

    /**
     * Remove a device token on logout.
     *
     * Called by Flutter on:
     *   - Logout
     *
     * DELETE /api/devices/token
     */
    public function remove(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        UserDevice::where('user_id', $request->user()->id)
            ->where('device_token', $request->device_token)
            ->delete();

        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'Device token removed successfully.',
        ]);
    }
}
