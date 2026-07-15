<?php

namespace Nilanjank\SchedulerHub\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Nilanjank\SchedulerHub\Models\SchedulerRun;

class ScheduledTaskFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SchedulerRun $run)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('[Scheduler Hub] Task failed: '.$this->run->command)
            ->view('scheduler-hub::emails.task-failed', ['run' => $this->run]);
    }
}
