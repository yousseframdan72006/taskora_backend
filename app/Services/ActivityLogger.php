<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public static function log($workspaceId, $description, $projectId = null)
    {
        try {
            if ($workspaceId) {
                Activity::create([
                    'workspace_id' => $workspaceId,
                    'project_id' => $projectId,
                    'description' => $description,
                ]);

                // نظام منطقي للحفاظ على نظافة قاعدة البيانات:
                // الاحتفاظ بآخر 50 حدث لكل مساحة عمل فقط، وحذف الأقدم تلقائياً
                $count = Activity::where('workspace_id', $workspaceId)->count();
                if ($count > 50) {
                    $idsToDelete = Activity::where('workspace_id', $workspaceId)
                        ->orderBy('created_at', 'asc')
                        ->limit($count - 50)
                        ->pluck('id');
                        
                    Activity::whereIn('id', $idsToDelete)->delete();
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to log activity: ' . $e->getMessage());
        }
    }
}
