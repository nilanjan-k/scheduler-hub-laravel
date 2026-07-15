<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11, 12, 13">
</p>

<h1 align="center">🛰️ Scheduler Hub</h1>

<p align="center">
  <b>A control center for Laravel's task scheduler.</b><br>
  See every scheduled task at a glance, trigger runs on demand, and never lose track of what happened — with a persistent run history and real failure alerts.
</p>

<p align="center">
  <a href="https://packagist.org/packages/nilanjank/scheduler-hub-laravel"><img src="https://img.shields.io/packagist/v/nilanjank/scheduler-hub-laravel.svg?style=flat-square&color=6366f1" alt="Latest Version on Packagist"></a>
  <a href="https://github.com/nilanjank/scheduler-hub-laravel/actions"><img src="https://img.shields.io/github/actions/workflow/status/nilanjank/scheduler-hub-laravel/run-tests.yml?branch=main&label=tests&style=flat-square" alt="Tests"></a>
  <a href="https://packagist.org/packages/nilanjank/scheduler-hub-laravel"><img src="https://img.shields.io/packagist/dt/nilanjank/scheduler-hub-laravel.svg?style=flat-square&color=6366f1" alt="Total Downloads"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-6366f1.svg?style=flat-square" alt="License MIT"></a>
</p>

---

## Why Scheduler Hub

Most Laravel scheduler dashboards stop at "here's a list of your cron jobs."
Scheduler Hub goes further:

| | |
|---|---|
| 🗂️ **Persistent run history** | Every scheduled run — not just manual ones — is recorded automatically by hooking into Laravel's native scheduler events. History stays accurate even if nobody ever opens the dashboard. |
| 🔔 **Failure notifications** | Mail, Slack, and signed generic webhooks, each independently toggleable. Know the moment something breaks, wherever your team already looks. |
| 🆔 **Stable task identity** | Tasks are identified by a hash of their command, expression, timezone, and description — not their position in the schedule list — so the "Run" button always hits the task you meant, even if the schedule changes between page load and click. |
| 🎨 **Clean, fast dashboard** | Live search, type filters, status badges, and an in-browser output viewer. No build step, no JS framework — just a single Blade view. |
| 🔒 **Locked down by default** | Disabled out of the box. Gate-based or callback-based authorization, configurable middleware, and manual execution off unless you explicitly turn it on. |

---

## Requirements

| Laravel | PHP |
|---|---|
| 11.x | 8.2+ |
| 12.x | 8.2+ |
| 13.x | 8.3+ |

## Installation

```bash
composer require nilanjank/scheduler-hub-laravel
php artisan vendor:publish --tag=scheduler-hub-config
php artisan migrate
```

## Quick start

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('inspire')
    ->everyMinute()
    ->description('Displays a random motivational quote.');
```

```env
SCHEDULER_HUB_ENABLED=true
```

Visit `http://localhost:8000/scheduler-hub` 🎉

To allow the **Run now** button:

```env
SCHEDULER_HUB_MANUAL_EXECUTION=true
```

The **Tasks** tab lists every registered task with its next run and, if
history is on, its most recent run status. The **History** tab shows every
recorded run, filterable by status, with full output/error on click.

---

## Configuration

Everything lives in `config/scheduler-hub.php`:

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

### 🔒 Security

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

> ⚠️ Never mount this behind only `['web']` middleware on a public app.

### 🔔 Notifications

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

Mail and Slack fire on `failure` by default (`notify_on`). The generic
webhook always fires for whichever statuses are in `notify_on`, so it can
double as a success ping too if you add `success` to that list.

Webhook requests are signed with an `X-Scheduler-Hub-Signature` header
(HMAC-SHA256 of the raw JSON body) whenever a secret is configured — verify
it on the receiving end before trusting the payload.

### 🗑️ Run history retention

Old runs don't accumulate forever — schedule the built-in prune command:

```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('scheduler-hub:prune')->daily();
```

Retention window is controlled by `history.retention_days` in the config.

---

## License

Released under the [MIT License](LICENSE.md).
