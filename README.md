# Scheduler Hub for Laravel

A dashboard for Laravel's task scheduler — see every registered task, its
next run, run it manually, and (unlike most scheduler-viewer packages) keep
a **persistent history** of every run with **failure notifications** over
mail, Slack, and generic webhooks.

Inspired by [scheduler-list-laravel](https://github.com/Akshayp2002/scheduler-list-laravel),
rebuilt with a different UI and three things that package doesn't have:

- **Persistent run history** — every scheduled run (not just manual ones)
  is recorded by hooking Laravel's native `ScheduledTaskStarting` /
  `ScheduledTaskFinished` / `ScheduledTaskFailed` / `ScheduledTaskSkipped`
  events, so history stays accurate whether or not anyone opens the dashboard.
- **Failure notifications** — mail, Slack (incoming webhook), and a signed
  generic webhook, each independently toggleable.
- **Stable task IDs** — tasks are identified by a hash of
  `command + expression + timezone + description`, not their position in
  `$schedule->events()`. The original approach breaks if the schedule
  changes between page load and clicking "Run."

## Installation

```bash
composer require nilanjank/scheduler-hub-laravel
php artisan vendor:publish --tag=scheduler-hub-config
php artisan migrate
```

> Rename the `nilanjank` namespace/vendor before publishing to Packagist —
> it's a placeholder throughout this package.

## Configuration

`config/scheduler-hub.php`:

```php
'enabled' => env('SCHEDULER_HUB_ENABLED', false),
'path' => env('SCHEDULER_HUB_PATH', 'scheduler-hub'),
'middleware' => ['web', 'auth'],
'ability' => env('SCHEDULER_HUB_ABILITY', 'viewSchedulerHub'),
'authorize' => null,

'manual_execution' => env('SCHEDULER_HUB_MANUAL_EXECUTION', false),
'output_limit' => env('SCHEDULER_HUB_OUTPUT_LIMIT', 12000),

'history' => [
    'enabled' => true,
    'track_scheduled_runs' => true,
    'retention_days' => 30,
    'per_page' => 25,
],

'notifications' => [
    'enabled' => false,
    'notify_on' => ['failure'],
    'channels' => [
        'mail' => ['enabled' => false, 'to' => null],
        'slack' => ['enabled' => false, 'webhook_url' => null],
        'webhook' => ['enabled' => false, 'url' => null, 'secret' => null],
    ],
],
```

### Security

The dashboard is **disabled by default**. To use it in production:

```env
SCHEDULER_HUB_ENABLED=true
SCHEDULER_HUB_MANUAL_EXECUTION=false
```

The default Gate only allows access in the `local` environment. Define it
yourself for anything else:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewSchedulerHub', fn ($user) => $user->is_admin);
```

Or use the `authorize` config callback instead of a Gate:

```php
'authorize' => fn (\Illuminate\Http\Request $request) => $request->user()?->is_admin === true,
```

Never mount this behind only `['web']` middleware on a public app.

### Notifications

Enable the channels you want:

```env
SCHEDULER_HUB_NOTIFICATIONS_ENABLED=true

SCHEDULER_HUB_NOTIFY_MAIL_ENABLED=true
SCHEDULER_HUB_NOTIFY_MAIL_TO="ops@example.com,alerts@example.com"

SCHEDULER_HUB_NOTIFY_SLACK_ENABLED=true
SCHEDULER_HUB_NOTIFY_SLACK_WEBHOOK_URL="https://hooks.slack.com/services/…"

SCHEDULER_HUB_NOTIFY_WEBHOOK_ENABLED=true
SCHEDULER_HUB_NOTIFY_WEBHOOK_URL="https://example.com/hooks/scheduler"
SCHEDULER_HUB_NOTIFY_WEBHOOK_SECRET="a-long-random-string"
```

Mail and Slack are sent for `failure` by default (`notify_on`). The generic
webhook always fires for whichever statuses are in `notify_on`, so it can
also be used for success pings if you set `notify_on` to include `success`.

Webhook requests are signed via an `X-Scheduler-Hub-Signature` header
(HMAC-SHA256 of the raw JSON body) when a secret is configured — verify it
on the receiving end before trusting the payload.

### Run history retention

```bash
php artisan schedule:list # sanity check your tasks are registered
```

Schedule the prune command yourself in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('scheduler-hub:prune')->daily();
```

## Usage

1. Register scheduled tasks as usual in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('inspire')
    ->everyMinute()
    ->description('Displays a random motivational quote.');
```

2. Enable and visit the dashboard:

```env
SCHEDULER_HUB_ENABLED=true
```

`http://localhost:8000/scheduler-hub`

3. The **Tasks** tab lists every registered task with its next run and,
   if history is on, its most recent run status. The **History** tab shows
   every recorded run, filterable by status, with full output/error on click.

4. To allow the **Run now** button:

```env
SCHEDULER_HUB_MANUAL_EXECUTION=true
```

## License

MIT.
