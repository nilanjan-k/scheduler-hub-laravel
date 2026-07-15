<?php

namespace Nilanjank\SchedulerHub\Support;

use Illuminate\Console\Scheduling\Event;

/**
 * Computes a stable identifier for a scheduled event.
 *
 * The upstream inspiration for this package keys tasks by their position
 * in $schedule->events(), which shifts if the schedule list changes between
 * page load and the "Run" click (e.g. another deploy, conditional
 * registration, package boot order). We hash stable event properties
 * instead, so an ID keeps meaning the same task across requests and across
 * the scheduled-run listeners.
 */
class TaskIdentifier
{
    public static function for(Event $event): string
    {
        $command = $event->command ?? $event->getSummaryForDisplay();

        $fingerprint = implode('|', [
            $command,
            $event->expression,
            $event->timezone ?: '',
            $event->description ?: '',
        ]);

        return substr(hash('sha256', $fingerprint), 0, 16);
    }
}
