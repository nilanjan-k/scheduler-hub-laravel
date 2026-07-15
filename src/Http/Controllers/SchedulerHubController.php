<?php

namespace Nilanjank\SchedulerHub\Http\Controllers;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Nilanjank\SchedulerHub\Models\SchedulerRun;
use Nilanjank\SchedulerHub\Notifications\NotificationDispatcher;
use Nilanjank\SchedulerHub\Support\ScheduleEventFormatter;

class SchedulerHubController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAccess($request);

        $events = ScheduleEventFormatter::formatAll();

        // Attach each task's most recent run (if history is enabled) so the
        // dashboard can show a live status badge without a second round trip.
        if (config('scheduler-hub.history.enabled', true)) {
            $lastRuns = SchedulerRun::query()
                ->whereIn('task_id', $events->pluck('id'))
                ->latest('id')
                ->get()
                ->unique('task_id')
                ->keyBy('task_id');

            $events = $events->map(function (array $task) use ($lastRuns) {
                $last = $lastRuns->get($task['id']);
                $task['last_run'] = $last ? [
                    'status' => $last->status,
                    'trigger' => $last->trigger,
                    'finished_at' => optional($last->finished_at)->diffForHumans(),
                    'duration_ms' => $last->duration_ms,
                ] : null;

                return $task;
            });
        }

        return app(ViewFactory::class)->make('scheduler-hub::dashboard', [
            'events' => $events,
            'historyEnabled' => config('scheduler-hub.history.enabled', true),
            'manualExecutionEnabled' => config('scheduler-hub.manual_execution', false),
        ]);
    }

    public function history(Request $request)
    {
        $this->authorizeAccess($request);

        if (! config('scheduler-hub.history.enabled', true)) {
            return response()->json(['success' => false, 'message' => 'History tracking is disabled.'], 403);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'in:running,success,failed,skipped'],
            'task_id' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = SchedulerRun::query()->latest('id');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['task_id'])) {
            $query->forTask($validated['task_id']);
        }

        $perPage = (int) config('scheduler-hub.history.per_page', 25);
        $paginator = $query->paginate($perPage, ['*'], 'page', $validated['page'] ?? 1);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function run(Request $request)
    {
        $this->authorizeAccess($request);

        if (! config('scheduler-hub.manual_execution', false)) {
            return response()->json([
                'success' => false,
                'output' => 'Manual execution is disabled in the configuration.',
            ], 403);
        }

        $validated = $request->validate([
            'id' => ['required', 'string'],
        ]);

        $event = ScheduleEventFormatter::findById($validated['id']);

        if (! $event) {
            return response()->json([
                'success' => false,
                'output' => 'Scheduled task not found. The schedule may have changed — refresh and try again.',
            ], 404);
        }

        $formatted = ScheduleEventFormatter::format($event);
        $historyEnabled = config('scheduler-hub.history.enabled', true);

        $run = $historyEnabled ? SchedulerRun::create([
            'task_id' => $formatted['id'],
            'command' => $formatted['command'],
            'type' => $formatted['type'],
            'description' => $formatted['description'],
            'status' => 'running',
            'trigger' => 'manual',
            'triggered_by_user_id' => $request->user()?->getAuthIdentifier(),
            'triggered_by_ip' => $request->ip(),
            'started_at' => now(),
        ]) : null;

        $startedAt = microtime(true);

        try {
            ob_start();
            $output = '';

            if (isset($event->command) && str_contains($event->command, 'artisan')) {
                $parts = preg_split('/\s+/', $event->command);
                $artisanIndex = -1;
                foreach ($parts as $idx => $part) {
                    if (trim($part, "'\" ") === 'artisan') {
                        $artisanIndex = $idx;
                        break;
                    }
                }

                if ($artisanIndex !== -1 && isset($parts[$artisanIndex + 1])) {
                    $commandName = trim($parts[$artisanIndex + 1], "'\" ");

                    $arguments = [];
                    for ($i = $artisanIndex + 2; $i < count($parts); $i++) {
                        $arg = trim($parts[$i], "'\" ");
                        if ($arg === '') {
                            continue;
                        }

                        if (str_starts_with($arg, '-')) {
                            if (str_contains($arg, '=')) {
                                [$key, $val] = explode('=', $arg, 2);
                                $arguments[$key] = trim($val, "'\" ");
                            } else {
                                $arguments[$arg] = true;
                            }
                        } else {
                            $arguments[] = $arg;
                        }
                    }

                    ob_get_clean();
                    Artisan::call($commandName, $arguments);
                    $output = Artisan::output();
                } else {
                    $event->run(app());
                    $output = ob_get_clean();
                }
            } else {
                $event->run(app());
                $output = ob_get_clean();
            }

            if ($output === '') {
                $output = 'Task executed successfully (no output returned).';
            }

            $outputLimit = (int) config('scheduler-hub.output_limit', 12000);
            if ($outputLimit > 0 && strlen($output) > $outputLimit) {
                $output = substr($output, 0, $outputLimit)."\n\n[Output truncated]";
            }

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($run) {
                $run->update([
                    'status' => 'success',
                    'output' => $output,
                    'finished_at' => now(),
                    'duration_ms' => $durationMs,
                ]);
                app(NotificationDispatcher::class)->dispatch($run->fresh());
            }

            Log::info('[scheduler-hub] Manual run completed.', [
                'task_id' => $formatted['id'],
                'user_id' => $request->user()?->getAuthIdentifier(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['success' => true, 'output' => trim($output)]);
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if ($run) {
                $run->update([
                    'status' => 'failed',
                    'error' => substr($e->getMessage(), 0, 5000),
                    'finished_at' => now(),
                    'duration_ms' => $durationMs,
                ]);
                app(NotificationDispatcher::class)->dispatch($run->fresh());
            }

            Log::error('[scheduler-hub] Manual run failed: '.$e->getMessage(), [
                'task_id' => $formatted['id'],
                'exception' => $e,
                'user_id' => $request->user()?->getAuthIdentifier(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'output' => 'Execution failed. Check the Laravel logs for details.',
            ], 500);
        }
    }

    private function authorizeAccess(Request $request): void
    {
        $callback = config('scheduler-hub.authorize');

        if ($callback !== null) {
            abort_unless(is_callable($callback), 403);
            abort_unless((bool) app()->call($callback, ['request' => $request]), 403);

            return;
        }

        $ability = config('scheduler-hub.ability', 'viewSchedulerHub');

        abort_unless(is_string($ability) && $ability !== '', 403);
        abort_unless(Gate::allows($ability), 403);
    }
}
