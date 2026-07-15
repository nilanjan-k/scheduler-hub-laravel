<?php

namespace Nilanjank\SchedulerHub\Support;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;

class ScheduleEventFormatter
{
    /**
     * Force-load the host application's schedule (Kernel + routes/console.php)
     * and return every registered event.
     *
     * @return Event[]
     */
    public static function loadEvents(): array
    {
        $schedule = app(Schedule::class);
        $kernel = app(ConsoleKernelContract::class);

        $kernel->resolveConsoleSchedule();

        if (file_exists(base_path('routes/console.php'))) {
            try {
                (static function () {
                    include_once base_path('routes/console.php');
                })();
            } catch (\Throwable $e) {
                // Fail-safe: a broken console.php shouldn't break the dashboard.
            }
        }

        return $schedule->events();
    }

    public static function findById(string $id): ?Event
    {
        foreach (self::loadEvents() as $event) {
            if (TaskIdentifier::for($event) === $id) {
                return $event;
            }
        }

        return null;
    }

    public static function formatAll(): Collection
    {
        return collect(self::loadEvents())->map(fn (Event $event) => self::format($event));
    }

    public static function format(Event $event): array
    {
        $command = $event->command ?? '';
        $summary = $event->getSummaryForDisplay();

        $cleanCommand = $command;
        if (empty($cleanCommand) && ! empty($summary)) {
            $cleanCommand = $summary;
        } elseif (preg_match('/(?:\b|["\'])artisan(?:\b|["\'])\s+(.*)$/i', $cleanCommand, $matches)) {
            $cleanCommand = 'php artisan '.trim($matches[1], '"\' ');
        } else {
            $cleanCommand = preg_replace('/^["\']?[^"\']+\bphp(?:\.exe)?["\']?\s+/', 'php ', $cleanCommand);
            $cleanCommand = trim($cleanCommand, "'\" ");
        }

        $type = 'Callback';
        if ($event instanceof CallbackEvent) {
            $type = 'Callback';
        } elseif (str_contains($command, 'artisan')) {
            $type = 'Artisan';
        } elseif (! empty($command)) {
            $type = 'Shell';
        }

        $nextRun = null;
        $nextRunDiff = null;
        try {
            $nextRunDate = Carbon::parse($event->nextRunDate());
            $nextRun = $nextRunDate->toDateTimeString();
            $nextRunDiff = $nextRunDate->diffForHumans();
        } catch (\Throwable $e) {
            // Fail-safe: an unparseable expression shouldn't break the dashboard.
        }

        $constraints = [];
        if ($event->withoutOverlapping) {
            $constraints[] = 'Without Overlapping';
        }
        if ($event->onOneServer) {
            $constraints[] = 'On One Server';
        }
        if ($event->evenInMaintenanceMode) {
            $constraints[] = 'In Maintenance';
        }
        if ($event->runInBackground) {
            $constraints[] = 'Background';
        }
        if (! empty($event->environments)) {
            $constraints[] = 'Env: '.implode(', ', $event->environments);
        }

        return [
            'id' => TaskIdentifier::for($event),
            'raw_command' => $command,
            'command' => $cleanCommand,
            'summary' => $summary ?: $cleanCommand,
            'expression' => $event->expression,
            'timezone' => $event->timezone ?: config('app.timezone'),
            'next_run' => $nextRun,
            'next_run_diff' => $nextRunDiff,
            'description' => $event->description ?: 'No description provided.',
            'type' => $type,
            'constraints' => $constraints,
        ];
    }
}
