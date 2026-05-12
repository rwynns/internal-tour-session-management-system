<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Record an activity log entry.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function log(
        string $action,
        string $subjectType,
        ?int $subjectId,
        string $description,
        ?array $metadata = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
