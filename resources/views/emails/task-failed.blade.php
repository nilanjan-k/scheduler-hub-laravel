<!DOCTYPE html>
<html>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f4f5f7; padding:24px; color:#1f2430;">
    <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:10px; border:1px solid #e5e7eb; overflow:hidden;">
        <div style="background:#dc2626; padding:16px 24px;">
            <span style="color:#fff; font-weight:600; font-size:14px; letter-spacing:.02em;">SCHEDULED TASK FAILED</span>
        </div>
        <div style="padding:24px;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr>
                    <td style="padding:6px 0; color:#6b7280; width:120px;">Command</td>
                    <td style="padding:6px 0; font-family:monospace;">{{ $run->command }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Type</td>
                    <td style="padding:6px 0;">{{ $run->type }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Description</td>
                    <td style="padding:6px 0;">{{ $run->description ?: '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Trigger</td>
                    <td style="padding:6px 0;">{{ ucfirst($run->trigger) }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Started</td>
                    <td style="padding:6px 0;">{{ $run->started_at?->toDateTimeString() ?? '—' }}</td>
                </tr>
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">Duration</td>
                    <td style="padding:6px 0;">{{ $run->duration_ms !== null ? number_format($run->duration_ms).' ms' : '—' }}</td>
                </tr>
            </table>

            @if ($run->error)
                <p style="margin:20px 0 6px; color:#6b7280; font-size:13px;">Error</p>
                <pre style="background:#111827; color:#fca5a5; padding:12px; border-radius:6px; font-size:12px; overflow-x:auto; white-space:pre-wrap;">{{ $run->error }}</pre>
            @endif
        </div>
    </div>
</body>
</html>
