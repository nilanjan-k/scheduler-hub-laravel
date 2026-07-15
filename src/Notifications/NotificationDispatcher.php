<?php

namespace Nilanjank\SchedulerHub\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Nilanjank\SchedulerHub\Mail\ScheduledTaskFailedMail;
use Nilanjank\SchedulerHub\Models\SchedulerRun;

class NotificationDispatcher
{
    public function dispatch(SchedulerRun $run): void
    {
        if (! config('scheduler-hub.notifications.enabled', false)) {
            return;
        }

        $notifyOn = config('scheduler-hub.notifications.notify_on', ['failure']);
        $statusEvent = $run->status === 'failed' ? 'failure' : 'success';

        if (! in_array($statusEvent, $notifyOn, true)) {
            return;
        }

        // v1 ships failure-focused templates/payloads for mail + Slack; the
        // generic webhook always gets a full JSON payload regardless of status.
        if ($statusEvent === 'failure') {
            $this->sendMail($run);
            $this->sendSlack($run);
        }

        $this->sendWebhook($run);
    }

    protected function sendMail(SchedulerRun $run): void
    {
        $config = config('scheduler-hub.notifications.channels.mail');

        if (! ($config['enabled'] ?? false) || empty($config['to'])) {
            return;
        }

        $recipients = array_filter(array_map('trim', explode(',', $config['to'])));

        if (empty($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new ScheduledTaskFailedMail($run));
        } catch (\Throwable $e) {
            Log::warning('[scheduler-hub] Failed to send failure email.', ['exception' => $e->getMessage()]);
        }
    }

    protected function sendSlack(SchedulerRun $run): void
    {
        $config = config('scheduler-hub.notifications.channels.slack');

        if (! ($config['enabled'] ?? false) || empty($config['webhook_url'])) {
            return;
        }

        $payload = [
            'text' => sprintf(
                ':red_circle: *Scheduled task failed:* `%s`%s',
                $run->command,
                $run->description ? " — {$run->description}" : ''
            ),
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => sprintf(
                            "*:red_circle: Scheduled task failed*\n*Command:* `%s`\n*Type:* %s\n*Trigger:* %s\n*Started:* %s",
                            $run->command,
                            $run->type,
                            ucfirst($run->trigger),
                            optional($run->started_at)->toDateTimeString() ?? '—'
                        ),
                    ],
                ],
            ],
        ];

        if ($run->error) {
            $payload['blocks'][] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '```'.substr($run->error, 0, 2900).'```',
                ],
            ];
        }

        try {
            Http::timeout(5)->post($config['webhook_url'], $payload);
        } catch (\Throwable $e) {
            Log::warning('[scheduler-hub] Failed to send Slack notification.', ['exception' => $e->getMessage()]);
        }
    }

    protected function sendWebhook(SchedulerRun $run): void
    {
        $config = config('scheduler-hub.notifications.channels.webhook');

        if (! ($config['enabled'] ?? false) || empty($config['url'])) {
            return;
        }

        $payload = [
            'task_id' => $run->task_id,
            'command' => $run->command,
            'type' => $run->type,
            'description' => $run->description,
            'status' => $run->status,
            'trigger' => $run->trigger,
            'started_at' => optional($run->started_at)->toIso8601String(),
            'finished_at' => optional($run->finished_at)->toIso8601String(),
            'duration_ms' => $run->duration_ms,
            'error' => $run->error,
        ];

        $request = Http::timeout(5)->asJson();

        if (! empty($config['secret'])) {
            $signature = hash_hmac('sha256', json_encode($payload), $config['secret']);
            $request = $request->withHeaders(['X-Scheduler-Hub-Signature' => $signature]);
        }

        try {
            $request->post($config['url'], $payload);
        } catch (\Throwable $e) {
            Log::warning('[scheduler-hub] Failed to send webhook notification.', ['exception' => $e->getMessage()]);
        }
    }
}
