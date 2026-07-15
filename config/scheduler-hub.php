<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    // Disabled by default. Flip this on deliberately per environment.
    'enabled' => env('SCHEDULER_HUB_ENABLED', false),

    // URL path where the dashboard is mounted.
    'path' => env('SCHEDULER_HUB_PATH', 'scheduler-hub'),

    // Middleware stack for every route the package registers.
    'middleware' => ['web', 'auth'],

    // Gate ability checked on every request. The package only auto-defines
    // this Gate to allow access in the `local` environment — define it
    // yourself for anything else.
    'ability' => env('SCHEDULER_HUB_ABILITY', 'viewSchedulerHub'),

    // Optional callback for authorization instead of a Gate, e.g.:
    // 'authorize' => fn (\Illuminate\Http\Request $request) => $request->user()?->is_admin === true,
    'authorize' => null,

    /*
    |--------------------------------------------------------------------------
    | Manual execution
    |--------------------------------------------------------------------------
    */

    'manual_execution' => env('SCHEDULER_HUB_MANUAL_EXECUTION', false),

    // Max characters of captured output returned to the browser / stored in history.
    'output_limit' => env('SCHEDULER_HUB_OUTPUT_LIMIT', 12000),

    /*
    |--------------------------------------------------------------------------
    | Run history
    |--------------------------------------------------------------------------
    | Every scheduled run (not just manual ones) is recorded via Laravel's
    | native ScheduledTaskStarting / ScheduledTaskFinished / ScheduledTaskFailed
    | events, so history stays accurate even for tasks nobody clicks "Run" on.
    */

    'history' => [
        'enabled' => env('SCHEDULER_HUB_HISTORY_ENABLED', true),

        // Track runs that come from `schedule:run` (real cron), in addition
        // to manual runs triggered from the dashboard.
        'track_scheduled_runs' => env('SCHEDULER_HUB_TRACK_SCHEDULED_RUNS', true),

        // Runs older than this are pruned by the `scheduler-hub:prune` command.
        'retention_days' => env('SCHEDULER_HUB_HISTORY_RETENTION_DAYS', 30),

        // How many rows to show per page in the History tab.
        'per_page' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    | Fired when a tracked run finishes. `notify_on` controls which statuses
    | trigger a notification; each channel below can be toggled independently.
    */

    'notifications' => [
        'enabled' => env('SCHEDULER_HUB_NOTIFICATIONS_ENABLED', false),

        // Any of: 'failure', 'success'
        'notify_on' => ['failure'],

        'channels' => [
            'mail' => [
                'enabled' => env('SCHEDULER_HUB_NOTIFY_MAIL_ENABLED', false),
                // Comma-separated list of recipient addresses.
                'to' => env('SCHEDULER_HUB_NOTIFY_MAIL_TO'),
            ],

            'slack' => [
                'enabled' => env('SCHEDULER_HUB_NOTIFY_SLACK_ENABLED', false),
                'webhook_url' => env('SCHEDULER_HUB_NOTIFY_SLACK_WEBHOOK_URL'),
            ],

            'webhook' => [
                'enabled' => env('SCHEDULER_HUB_NOTIFY_WEBHOOK_ENABLED', false),
                'url' => env('SCHEDULER_HUB_NOTIFY_WEBHOOK_URL'),
                // If set, requests are signed with an `X-Scheduler-Hub-Signature`
                // header (HMAC SHA-256 of the raw JSON body) so the receiver
                // can verify authenticity.
                'secret' => env('SCHEDULER_HUB_NOTIFY_WEBHOOK_SECRET'),
            ],
        ],
    ],

];
