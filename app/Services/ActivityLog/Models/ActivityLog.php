<?php

namespace App\Services\ActivityLog\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $log_type
 * @property string $action
 * @property string|null $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property array<array-key, mixed>|null $properties
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent|null $causer
 * @property-read Model|\Eloquent|null $subject
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog byCauser(\Illuminate\Database\Eloquent\Model $causer)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog forAction(string $action)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog forSubject(\Illuminate\Database\Eloquent\Model $subject)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog forType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCauserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCauserType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereLogType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereProperties($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereSubjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog whereUserAgent($value)
 * @mixin \Eloquent
 */
class ActivityLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'properties' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        if (! isset($this->table)) {
            $this->setTable(config('activity_log.table_name', 'activity_logs'));
        }

        parent::__construct($attributes);
    }

    /**
     * The subject of the activity.
     */
    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * The user who caused the activity.
     */
    public function causer()
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter logs by type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('log_type', $type);
    }

    /**
     * Scope to filter logs by action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter logs by subject.
     */
    public function scopeForSubject($query, Model $subject)
    {
        return $query->where('subject_type', get_class($subject))
                     ->where('subject_id', $subject->getKey());
    }

    /**
     * Scope to filter logs by causer.
     */
    public function scopeByCauser($query, Model $causer)
    {
        return $query->where('causer_type', get_class($causer))
                     ->where('causer_id', $causer->getKey());
    }
}
