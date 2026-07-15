<?php

namespace Nilanjank\SchedulerHub;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Nilanjank\SchedulerHub\Console\PruneRunsCommand;
use Nilanjank\SchedulerHub\Listeners\SchedulerEventSubscriber;

class SchedulerHubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scheduler-hub.php', 'scheduler-hub');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'scheduler-hub');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/scheduler-hub.php' => config_path('scheduler-hub.php'),
            ], 'scheduler-hub-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/scheduler-hub'),
            ], 'scheduler-hub-views');

            $this->commands([
                PruneRunsCommand::class,
            ]);
        }

        $this->registerDefaultGate();
        $this->registerScheduleEventSubscriber();
    }

    protected function registerDefaultGate(): void
    {
        $ability = config('scheduler-hub.ability', 'viewSchedulerHub');

        if (! is_string($ability) || $ability === '' || Gate::has($ability)) {
            return;
        }

        // Deliberately conservative: only auto-allow in `local`. Anything
        // else must define this Gate (or use `authorize`) explicitly.
        Gate::define($ability, fn (?Authenticatable $user = null): bool => $this->app->environment('local'));
    }

    protected function registerScheduleEventSubscriber(): void
    {
        $this->app->make(Dispatcher::class)
            ->subscribe(SchedulerEventSubscriber::class);
    }
}
