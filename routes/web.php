<?php

use Illuminate\Support\Facades\Route;
use Nilanjank\SchedulerHub\Http\Controllers\SchedulerHubController;

if (config('scheduler-hub.enabled', false)) {
    Route::middleware(config('scheduler-hub.middleware', ['web', 'auth']))
        ->prefix(config('scheduler-hub.path', 'scheduler-hub'))
        ->group(function () {
            Route::get('/', [SchedulerHubController::class, 'index'])->name('scheduler-hub.index');
            Route::get('/history', [SchedulerHubController::class, 'history'])->name('scheduler-hub.history');
            Route::post('/run', [SchedulerHubController::class, 'run'])->name('scheduler-hub.run');
        });
}
