<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\UserDevice;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of retry attempts before failing.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying a failed job.
     */
    public int $backoff = 10;

    /**
     * @param  array       $userIds        Target user UUIDs
     * @param  string      $title          Notification title
     * @param  string      $body           Notification body
     * @param  array       $data           Extra payload (task_id, type, etc.)
     * @param  string|null $excludeUserId  Actor user — will NOT receive the notification
     */
    public function __construct(
        public readonly array   $userIds,
        public readonly string  $title,
        public readonly string  $body,
        public readonly array   $data = [],
        public readonly ?string $excludeUserId = null,
    ) {}

    /**
     * Execute the job.
     * 1. Filter out the actor (self-notification prevention).
     * 2. Collect device tokens.
     * 3. Send FCM push to each device.
     * 4. Clean up invalid/expired tokens.
     * 5. Save in-app notification records to DB (one per user).
     */
    public function handle(FcmService $fcm): void
    {
        // --- 1. Determine target users (exclude actor) ---
        $targetUserIds = collect($this->userIds)
            ->unique()
            ->when(
                $this->excludeUserId,
                fn ($col) => $col->reject(fn ($id) => $id === $this->excludeUserId)
            )
            ->values()
            ->toArray();

        if (empty($targetUserIds)) {
            return;
        }

        // --- 1b. Deduplication: Prevent identical notifications within 3 seconds ---
        $fingerprint = 'notif_lock_' . md5(json_encode([
            'users' => collect($targetUserIds)->sort()->values()->toArray(),
            'title' => $this->title,
            'body'  => $this->body,
            'data'  => $this->data,
        ]));

        $lock = \Illuminate\Support\Facades\Cache::lock($fingerprint, 3);
        if (!$lock->get()) {
            \Illuminate\Support\Facades\Log::info('Duplicate notification suppressed', ['fingerprint' => $fingerprint]);
            return;
        }

        // --- 2. Fetch all device tokens for target users ---
        $devices = UserDevice::whereIn('user_id', $targetUserIds)
            ->select('device_token', 'user_id')
            ->distinct('device_token')
            ->get();

        // --- 3. Send FCM & collect invalid tokens ---
        $invalidTokens = [];

        foreach ($devices as $device) {
            $result = $fcm->send(
                $device->device_token,
                $this->title,
                $this->body,
                $this->data
            );

            if (!$result['success'] && $result['invalid_token']) {
                $invalidTokens[] = $device->device_token;
            }
        }

        // --- 4. Purge invalid tokens (UNREGISTERED / expired) ---
        if (!empty($invalidTokens)) {
            UserDevice::whereIn('device_token', $invalidTokens)->delete();
            Log::info('Purged invalid FCM tokens', ['count' => count($invalidTokens)]);
        }

        // --- 5. Save in-app DB notifications (Hybrid system) ---
        $type    = $this->data['type'] ?? 'general';
        $payload = json_encode([
            'title' => $this->title,
            'body'  => $this->body,
            ...$this->data,
        ]);

        $records = array_map(fn ($userId) => [
            'id'               => \Illuminate\Support\Str::uuid()->toString(),
            'type'             => $type,
            'notifiable_type'  => \App\Models\User::class,
            'notifiable_id'    => $userId,
            'data'             => $payload,
            'read_at'          => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ], $targetUserIds);

        // Bulk insert for performance
        Notification::insert($records);
    }
}
