<?php

namespace Nilanjank\SchedulerHub\Listeners;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Scheduling\Events\ScheduledTaskFailed;
use Illuminate\Console\Scheduling\Events\ScheduledTaskFinished;
use Illuminate\Console\Scheduling\Events\ScheduledTaskSkipped;
use Illuminate\Console\Scheduling\Events\ScheduledTaskStarting;
use Illuminate\Events\Dispatcher;
use Nilanjank\SchedulerHub\Models\SchedulerRun;
use Nilanjank\SchedulerHub\Notifications\NotificationDispatcher;
use Nilanjank\SchedulerHub\Support\ScheduleEventFormatter;
use Nilanjank\SchedulerHub\Support\TaskIdentifier;

/**
 * Subscribes to Laravel's own scheduler lifecycle events so that every real
 * `schedule:run` execution is recorded — not only runs triggered manually
 * from the dashboard. This is what makes History reflect reality even if
 * nobody ever opens the dashboard.
 */
class SchedulerEventSubscriber
{
    /** @var array<int, int> Maps spl_object_id(Event) => SchedulerRun id for in-flight runs. */
    protected array $inFlight = [];

    public function subscribe(Dispatcher $events): void
    {
        if (! config('scheduler-hub.history.enabled', true)
            || ! config('scheduler-hub.history.track_scheduled_runs', true)) {
            return;
        }

        $events->listen(ScheduledTaskStarting::class, [$this, 'handleStarting']);
        $events->listen(ScheduledTaskFinished::class, [$this, 'handleFinished']);
        $events->listen(ScheduledBackgroundTaskFinished::class, [$this, 'handleFinished']);
        $events->listen(ScheduledTaskSkipped::class, [$this, 'handleSkipped']);
        $events->listen(ScheduledTaskFailed::class, [$this, 'handleFailed']);
    }

    public function handleStarting(ScheduledTaskStarting $event): void
    {
        $formatted = ScheduleEventFormatter::format($event->task);

        $run = SchedulerRun::create([
            'task_id' => $formatted['id'],
            'command' => $formatted['command'],
            'type' => $formatted['type'],
            'description' => $formatted['description'],
            'status' => 'running',
            'trigger' => 'scheduled',
            'started_at' => now(),
        ]);

        $this->inFlight[spl_object_id($event->task)] = $run->id;
    }

    public function handleFinished(ScheduledTaskFinished|ScheduledBackgroundTaskFinished $event): void
    {
        $run = $this->resolveRun($event->task);

        if (! $run) {
            return;
        }

        $run->status = 'success';
        $run->finished_at = now();
        $run->duration_ms = $this->durationMs($run, $event->runtime ?? null);
        $run->save();

        app(NotificationDispatcher::class)->dispatch($run);
    }

    public function handleSkipped(ScheduledTaskSkipped $event): void
    {
        $formatted = ScheduleEventFormatter::format($event->task);

        SchedulerRun::create([
            'task_id' => $formatted['id'],
            'command' => $formatted['command'],
            'type' => $formatted['type'],
            'description' => $formatted['description'],
            'status' => 'skipped',
            'trigger' => 'scheduled',
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    public function handleFailed(ScheduledTaskFailed $event): void
    {
        $run = $this->resolveRun($event->task);

        if (! $run) {
            $formatted = ScheduleEventFormatter::format($event->task);
            $run = SchedulerRun::create([
                'task_id' => $formatted['id'],
                'command' => $formatted['command'],
                'type' => $formatted['type'],
                'description' => $formatted['description'],
                'status' => 'failed',
                'trigger' => 'scheduled',
                'started_at' => now(),
            ]);
        }

        $run->status = 'failed';
        $run->finished_at = now();
        $run->duration_ms = $this->durationMs($run, null);
        $run->error = substr((string) $event->exception, 0, 5000);
        $run->save();

        app(NotificationDispatcher::class)->dispatch($run);
    }

    protected function resolveRun(Event $task): ?SchedulerRun
    {
        $id = $this->inFlight[spl_object_id($task)] ?? null;

        if ($id === null) {
            // Fall back to the most recent "running" row for this task hash —
            // covers edge cases like background tasks resolved in a fresh process.
            return SchedulerRun::forTask(TaskIdentifier::for($task))
                ->where('status', 'running')
                ->latest('id')
                ->first();
        }

        unset($this->inFlight[spl_object_id($task)]);

        return SchedulerRun::find($id);
    }

    protected function durationMs(SchedulerRun $run, ?float $runtimeSeconds): ?int
    {
        if ($runtimeSeconds !== null) {
            return (int) round($runtimeSeconds * 1000);
        }

        if ($run->started_at) {
            return (int) round($run->started_at->diffInMilliseconds(now()));
        }

        return null;
    }
}
