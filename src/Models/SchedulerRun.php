<?php

namespace Nilanjank\SchedulerHub\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $task_id
 * @property string $command
 * @property string $type
 * @property string|null $description
 * @property string $status
 * @property string $trigger
 * @property string|null $output
 * @property string|null $error
 * @property int|null $triggered_by_user_id
 * @property string|null $triggered_by_ip
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property int|null $duration_ms
 */
class SchedulerRun extends Model
{
    protected $table = 'scheduler_hub_runs';

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_ms' => 'integer',
    ];

    public function scopeForTask($query, string $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
