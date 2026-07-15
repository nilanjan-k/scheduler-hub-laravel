<?php

namespace Nilanjank\SchedulerHub\Console;

use Illuminate\Console\Command;
use Nilanjank\SchedulerHub\Models\SchedulerRun;

class PruneRunsCommand extends Command
{
    protected $signature = 'scheduler-hub:prune';

    protected $description = 'Delete scheduler-hub run history older than the configured retention period.';

    public function handle(): int
    {
        $days = (int) config('scheduler-hub.history.retention_days', 30);

        if ($days <= 0) {
            $this->info('Retention is disabled (retention_days <= 0). Nothing pruned.');

            return self::SUCCESS;
        }

        $deleted = SchedulerRun::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Pruned {$deleted} run(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
