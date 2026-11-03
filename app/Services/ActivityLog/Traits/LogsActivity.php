<?php

namespace App\Services\ActivityLog\Traits;

use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

/**
 * LogsActivity Trait - Attach this to any Eloquent model to enable automatic audit trails.
 *
 * HOW TO USE:
 * 1. Add 'use LogsActivity;' to your Model.
 * 2. (Optional) Define 'public string $activityLogType = "user";' to set the category.
 *
 * Automatically tracks: Created, Updated (with diff), and Deleted events.
 */
trait LogsActivity
{
    /**
     * Boot the trait to attach model events.
     * This method is automatically called by Laravel when the model boots.
     */
    protected static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            ActivityLogService::log(
                action: 'created',
                description: 'Record created in ' . (new \ReflectionClass($model))->getShortName(),
                subject: $model,
                properties: ['attributes' => $model->getAttributes()],
                type: static::resolveLogType($model)
            );
        });

        static::updated(function (Model $model) {
            $diff = ActivityLogService::getDiff($model);
            
            if (empty($diff['new'])) {
                return;
            }

            ActivityLogService::log(
                action: 'updated',
                description: 'Record updated in ' . (new \ReflectionClass($model))->getShortName(),
                subject: $model,
                properties: $diff,
                type: static::resolveLogType($model)
            );
        });

        static::deleted(function (Model $model) {
            ActivityLogService::log(
                action: 'deleted',
                description: 'Record deleted from ' . (new \ReflectionClass($model))->getShortName(),
                subject: $model,
                properties: ['attributes' => $model->getAttributes()],
                type: static::resolveLogType($model)
            );
        });
    }

    /**
     * Resolve the log type for the model.
     */
    protected static function resolveLogType(Model $model): string
    {
        if (method_exists($model, 'getActivityLogType')) {
            return $model->getActivityLogType();
        }

        if (property_exists($model, 'activityLogType')) {
            return $model->activityLogType;
        }

        // Default: If it's an Admin model, use 'admin', otherwise 'user'
        return ($model instanceof \Modules\Admins\Models\Admins) ? 'admin' : 'user';
    }
}
