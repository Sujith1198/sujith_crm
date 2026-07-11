<?php

namespace App\Services;

use App\Models\ActivityLog;

/**
 * ActivityLogService
 * Central logging service for all user and system actions.
 */
class ActivityLogService
{
    public function log(
        string $action,
        string $description,
        ?int $userId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $url = null,
        ?string $method = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'      => $userId ?? auth()->id(),
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'old_values'   => empty($oldValues) ? null : $oldValues,
            'new_values'   => empty($newValues) ? null : $newValues,
            'ip_address'   => $ip ?? request()->ip(),
            'user_agent'   => $userAgent ?? request()->userAgent(),
            'url'          => $url ?? request()->fullUrl(),
            'method'       => $method ?? request()->method(),
        ]);
    }

    public function logModel(
        string $action,
        string $description,
        mixed $model,
        array $oldValues = [],
        array $newValues = [],
    ): ActivityLog {
        return $this->log(
            action: $action,
            description: $description,
            subjectType: get_class($model),
            subjectId: $model->id,
            oldValues: $oldValues,
            newValues: $newValues,
        );
    }
}
