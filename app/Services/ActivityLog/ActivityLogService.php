<?php

namespace App\Services\ActivityLog;

use App\Services\ActivityLog\Models\ActivityLog;
use App\Services\ActivityLog\Resources\ActivityLogResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Admins\Models\Admins;

/**
 * ActivityLogService - A portable, self-contained service for audit trails.
 *
 * USAGE EXAMPLES:
 * 1. Manual Log:
 *    ActivityLogService::log(
 *        action: 'login',
 *        description: 'Admin logged into the system',
 *        properties: ['method' => 'web']
 *    );
 *
 * 2. Get Filtered Logs:
 *    $service = app(ActivityLogService::class);
 *    $logs = $service->getLogs(['type' => 'admin', 'action' => 'updated']);
 */
class ActivityLogService
{
    /**
     * Log an activity record manually.
     *
     * @param  string  $action  The action performed (e.g., 'created', 'updated', 'login')
     * @param  string|null  $description  Human-readable description of the activity
     * @param  Model|null  $subject  The model being acted upon (polymorphic)
     * @param  array  $properties  Extra data or diff (old vs new values)
     * @param  Model|null  $causer  The user who did the action (defaults to Auth::user())
     * @param  string|null  $type  Log category ('admin' or 'user'). Auto-detected if null.
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?Model $causer = null,
        ?string $type = null
    ): ActivityLog {
        $causer = $causer ?? Auth::user();

        // Auto-detect type if not provided
        if (! $type) {
            $type = ($causer instanceof Admins) ? 'admin' : 'user';
        }

        // If no causer and a default is configured, try to get the first one (for system actions)
        if (! $causer && $defaultCauser = config('activity_log.default_causer')) {
            $causer = $defaultCauser::first();
        }

        return ActivityLog::create([
            'log_type' => $type,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->getKey() : null,
            'causer_type' => $causer ? get_class($causer) : null,
            'causer_id' => $causer ? $causer->getKey() : null,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Get filtered activity logs.
     */
    public function getLogs(array $filters = [], int $perPage = 15)
    {
        $query = ActivityLog::with(['subject', 'causer'])->latest();

        // 0. Filter by Log Type (Admin vs User)
        if (isset($filters['type']) && ! empty($filters['type'])) {
            $query->where('log_type', $filters['type']);
        }

        // 1. Filter by Action (Event)
        if (isset($filters['action']) && ! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // 2. Filter by Model (Subject)
        if (isset($filters['subject_type']) && ! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (isset($filters['subject_id']) && ! empty($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }

        // 3. Filter by User (Causer)
        if (isset($filters['causer_id']) && ! empty($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
            if (isset($filters['causer_type']) && ! empty($filters['causer_type'])) {
                $query->where('causer_type', $filters['causer_type']);
            }
        }

        // 3b. Filter by a list of causers, used to keep the log of one
        //     company away from the log of another company.
        if (array_key_exists('causer_ids', $filters)) {
            $query->whereIn('causer_id', (array) $filters['causer_ids']);

            if (isset($filters['causer_type']) && ! empty($filters['causer_type'])) {
                $query->where('causer_type', $filters['causer_type']);
            }
        }

        // 4. Filter by Date Range (From - To)
        if (isset($filters['from']) && ! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (isset($filters['to']) && ! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        // 5. Search in Description
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $query->where('description', 'like', '%'.$filters['search'].'%');
        }

return $query->paginate($perPage);
    }

    /**
     * Build and store a CSV file for the chosen subject's activity logs.
     *
     * @return array{file_name: string, path: string, url: string}
     */
    public function exportSubjectCsv(int $subjectId): array
    {
        $logs = $this->getLogs(
            filters: ['subject_id' => $subjectId],
            perPage: 50000
        )->getCollection();

        $fileName = 'activity-log-export-'.$subjectId.'-'.now()->format('Ymd_His').'.csv';
        $path = 'exports/activity-logs/'.$fileName;

        $handle = fopen('php://temp', 'w');
        fputcsv($handle, ['ID', 'Log Type', 'Action', 'Description', 'User', 'IP Address', 'Created At']);

        ActivityLogResource::collection($logs)->each(function (ActivityLogResource $log) use ($handle): void {
            $data = $log->resolve();

            fputcsv($handle, [
                $data['id'],
                $data['log_type'],
                $data['action'],
                $data['description'],
                $data['causer']['name'],
                $data['ip_address'],
                $data['created_at'],
            ]);
        });

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Storage::disk('public')->put($path, $csv);

        return [
            'file_name' => $fileName,
            'path' => $path,
            'url' => route('api.activity_logs.export.download', ['fileName' => $fileName]),
        ];
    }

    /**
     * Resolve the storage path for an exported CSV file name.
     */
    public function exportFilePath(string $fileName): string
    {
        return 'exports/activity-logs/'.$fileName;
    }

    /**
     * Get the diff between old and new values.
     */
    public static function getDiff(Model $model): array
    {
        $ignored = config('activity_log.ignored_attributes', []);
        $changes = [
            'old' => [],
            'new' => [],
        ];
        foreach ($model->getChanges() as $attribute => $newValue) {
            if (in_array($attribute, $ignored)) {
                continue;
            }
            $changes['old'][$attribute] = $model->getOriginal($attribute);
            $changes['new'][$attribute] = $newValue;
        }

        return $changes;
    }
}
