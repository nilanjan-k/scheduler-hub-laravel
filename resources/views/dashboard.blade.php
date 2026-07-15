<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scheduler Hub</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232563eb' stroke-width='2'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M12 7v5l3 3'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f6f7f9;
            --surface: #ffffff;
            --border: #e5e7eb;
            --text: #14181f;
            --text-muted: #6b7280;
            --text-dim: #9ca3af;
            --primary: #2563eb;
            --primary-soft: #eff4ff;
            --success: #059669;
            --success-soft: #ecfdf5;
            --danger: #dc2626;
            --danger-soft: #fef2f2;
            --warning: #d97706;
            --warning-soft: #fffbeb;
            --neutral-soft: #f3f4f6;
            --radius: 10px;
            --shadow: 0 1px 2px rgba(16,24,40,.04), 0 1px 3px rgba(16,24,40,.06);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.5;
        }
        code, .mono { font-family: 'IBM Plex Mono', monospace; }

        .topbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 28px; background: var(--surface); border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 10;
        }
        .brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 15px; }
        .brand svg { color: var(--primary); }
        .brand small { color: var(--text-dim); font-weight: 500; margin-left: 4px; }

        .stats { display: flex; gap: 24px; font-size: 13px; color: var(--text-muted); }
        .stats b { color: var(--text); font-weight: 600; }

        .wrap { max-width: 1180px; margin: 0 auto; padding: 24px 28px 64px; }

        .tabs { display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
        .tab {
            padding: 10px 4px; margin-right: 20px; font-size: 14px; font-weight: 600; color: var(--text-dim);
            border-bottom: 2px solid transparent; cursor: pointer; background: none; border-top: none; border-left: none; border-right: none;
        }
        .tab.active { color: var(--primary); border-bottom-color: var(--primary); }

        .toolbar { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
        .input, .select {
            padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface);
            font-size: 13px; font-family: inherit; color: var(--text);
        }
        .input { flex: 1; min-width: 200px; }
        .input:focus, .select:focus { outline: 2px solid var(--primary-soft); border-color: var(--primary); }

        .chip-group { display: flex; gap: 6px; }
        .chip {
            padding: 7px 12px; border-radius: 999px; border: 1px solid var(--border); background: var(--surface);
            font-size: 12.5px; font-weight: 600; color: var(--text-muted); cursor: pointer;
        }
        .chip.active { background: var(--primary); border-color: var(--primary); color: #fff; }

        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow); }
        .task-list { display: flex; flex-direction: column; gap: 10px; }
        .task {
            padding: 16px 18px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        }
        .task-main { min-width: 0; flex: 1; }
        .task-command { font-family: 'IBM Plex Mono', monospace; font-size: 13.5px; font-weight: 500; word-break: break-all; }
        .task-desc { color: var(--text-muted); font-size: 12.5px; margin-top: 3px; }
        .task-meta { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }

        .badge { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; }
        .badge-type-Artisan { background: var(--primary-soft); color: var(--primary); }
        .badge-type-Callback { background: #f3e8ff; color: #7c3aed; }
        .badge-type-Shell { background: var(--neutral-soft); color: var(--text-muted); }
        .badge-constraint { background: var(--neutral-soft); color: var(--text-muted); }
        .badge-success { background: var(--success-soft); color: var(--success); }
        .badge-failed { background: var(--danger-soft); color: var(--danger); }
        .badge-running { background: var(--warning-soft); color: var(--warning); }
        .badge-skipped { background: var(--neutral-soft); color: var(--text-dim); }
        .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

        .task-side { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 6px; min-width: 160px; }
        .next-run { font-size: 12.5px; color: var(--text-muted); }
        .next-run b { color: var(--text); font-weight: 600; }

        .btn {
            padding: 7px 14px; border-radius: 7px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: 1px solid var(--border);
            background: var(--surface); color: var(--text);
        }
        .btn-primary { background: var(--primary); border-color: var(--primary); color: #fff; }
        .btn-primary:disabled { opacity: .5; cursor: not-allowed; }
        .btn:hover:not(:disabled) { filter: brightness(0.98); }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 10px 14px; color: var(--text-dim); font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: .03em; border-bottom: 1px solid var(--border); }
        td { padding: 12px 14px; border-bottom: 1px solid var(--border); vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        .empty { text-align: center; padding: 48px 16px; color: var(--text-dim); font-size: 13.5px; }

        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 16px; }

        .overlay {
            display: none; position: fixed; inset: 0; background: rgba(15,20,30,.5); z-index: 50;
            align-items: center; justify-content: center; padding: 24px;
        }
        .overlay.open { display: flex; }
        .modal {
            background: #0d1117; color: #e6edf3; width: min(720px, 100%); max-height: 80vh; border-radius: 10px;
            display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,.4);
        }
        .modal-header { padding: 14px 18px; border-bottom: 1px solid #21262d; display: flex; justify-content: space-between; align-items: center; }
        .modal-header span { font-family: 'IBM Plex Mono', monospace; font-size: 13px; color: #8b949e; }
        .modal-close { background: none; border: none; color: #8b949e; font-size: 18px; cursor: pointer; }
        .modal-body { padding: 16px 18px; overflow-y: auto; font-family: 'IBM Plex Mono', monospace; font-size: 12.5px; white-space: pre-wrap; }
        .modal-body.err { color: #ff7b72; }
        .modal-body.ok { color: #7ee787; }

        @media (max-width: 720px) {
            .wrap { padding: 16px; }
            .task { flex-direction: column; align-items: flex-start; }
            .task-side { align-items: flex-start; text-align: left; }
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="brand">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        Scheduler Hub <small>v1</small>
    </div>
    <div class="stats">
        <span><b>{{ $events->count() }}</b> tasks</span>
        @if ($manualExecutionEnabled)
            <span style="color: var(--success);">● manual execution on</span>
        @else
            <span style="color: var(--text-dim);">● manual execution off</span>
        @endif
    </div>
</div>

<div class="wrap">
    <div class="tabs">
        <button class="tab active" data-tab="tasks" onclick="switchTab('tasks')">Tasks</button>
        @if ($historyEnabled)
            <button class="tab" data-tab="history" onclick="switchTab('history')">History</button>
        @endif
    </div>

    {{-- TASKS TAB --}}
    <div id="tab-tasks">
        <div class="toolbar">
            <input type="text" class="input" id="search" placeholder="Search by command, description, or expression..." oninput="filterTasks()">
            <div class="chip-group" id="type-filters">
                <button class="chip active" data-type="all" onclick="setTypeFilter('all')">All</button>
                <button class="chip" data-type="Artisan" onclick="setTypeFilter('Artisan')">Artisan</button>
                <button class="chip" data-type="Callback" onclick="setTypeFilter('Callback')">Callback</button>
                <button class="chip" data-type="Shell" onclick="setTypeFilter('Shell')">Shell</button>
            </div>
        </div>

        <div class="card">
            <div class="task-list" id="task-list" style="padding: 6px;">
                @forelse ($events as $task)
                    <div class="task" data-command="{{ strtolower($task['command'].' '.$task['description'].' '.$task['expression']) }}" data-type="{{ $task['type'] }}">
                        <div class="task-main">
                            <div class="task-command">{{ $task['command'] }}</div>
                            <div class="task-desc">{{ $task['description'] }}</div>
                            <div class="task-meta">
                                <span class="badge badge-type-{{ $task['type'] }}">{{ $task['type'] }}</span>
                                <span class="badge badge-constraint mono">{{ $task['expression'] }}</span>
                                <span class="badge badge-constraint">{{ $task['timezone'] }}</span>
                                @foreach ($task['constraints'] as $c)
                                    <span class="badge badge-constraint">{{ $c }}</span>
                                @endforeach
                                @if (!empty($task['last_run']))
                                    <span class="badge badge-{{ $task['last_run']['status'] }}">
                                        <span class="dot"></span>
                                        last run: {{ $task['last_run']['status'] }} ({{ $task['last_run']['trigger'] }}) {{ $task['last_run']['finished_at'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="task-side">
                            <div class="next-run">next run<br><b>{{ $task['next_run_diff'] ?? 'N/A' }}</b></div>
                            @if ($manualExecutionEnabled)
                                <button class="btn btn-primary" onclick="runTask('{{ $task['id'] }}', this)">Run now</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty">No scheduled tasks found. Register some in routes/console.php.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- HISTORY TAB --}}
    @if ($historyEnabled)
        <div id="tab-history" style="display:none;">
            <div class="toolbar">
                <div class="chip-group" id="status-filters">
                    <button class="chip active" data-status="" onclick="setStatusFilter('')">All</button>
                    <button class="chip" data-status="success" onclick="setStatusFilter('success')">Success</button>
                    <button class="chip" data-status="failed" onclick="setStatusFilter('failed')">Failed</button>
                    <button class="chip" data-status="running" onclick="setStatusFilter('running')">Running</button>
                    <button class="chip" data-status="skipped" onclick="setStatusFilter('skipped')">Skipped</button>
                </div>
            </div>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Command</th>
                            <th>Status</th>
                            <th>Trigger</th>
                            <th>Started</th>
                            <th>Duration</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="history-body">
                        <tr><td colspan="6" class="empty">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="history-pagination"></div>
        </div>
    @endif
</div>

<div class="overlay" id="overlay">
    <div class="modal">
        <div class="modal-header">
            <span id="modal-title">output</span>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body" id="modal-body"></div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const runUrl = @json(url(config('scheduler-hub.path', 'scheduler-hub').'/run'));
    const historyUrl = @json(url(config('scheduler-hub.path', 'scheduler-hub').'/history'));

    // ---- Tabs ----
    function switchTab(tab) {
        document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
        document.getElementById('tab-tasks').style.display = tab === 'tasks' ? 'block' : 'none';
        const historyTab = document.getElementById('tab-history');
        if (historyTab) {
            historyTab.style.display = tab === 'history' ? 'block' : 'none';
            if (tab === 'history') loadHistory(1);
        }
    }

    // ---- Task search/filter ----
    let typeFilter = 'all';
    function setTypeFilter(type) {
        typeFilter = type;
        document.querySelectorAll('#type-filters .chip').forEach(c => c.classList.toggle('active', c.dataset.type === type));
        filterTasks();
    }
    function filterTasks() {
        const q = document.getElementById('search').value.toLowerCase();
        document.querySelectorAll('#task-list .task').forEach(el => {
            const matchesText = el.dataset.command.includes(q);
            const matchesType = typeFilter === 'all' || el.dataset.type === typeFilter;
            el.style.display = (matchesText && matchesType) ? 'flex' : 'none';
        });
    }

    // ---- Run task ----
    function runTask(id, btn) {
        btn.disabled = true;
        const original = btn.textContent;
        btn.textContent = 'Running...';

        fetch(runUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ id }),
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            openModal(id, data.output, ok && data.success);
        })
        .catch(() => openModal(id, 'Network error while running task.', false))
        .finally(() => {
            btn.disabled = false;
            btn.textContent = original;
        });
    }

    function openModal(title, output, success) {
        document.getElementById('modal-title').textContent = title;
        const body = document.getElementById('modal-body');
        body.textContent = output;
        body.className = 'modal-body ' + (success ? 'ok' : 'err');
        document.getElementById('overlay').classList.add('open');
    }
    function closeModal() { document.getElementById('overlay').classList.remove('open'); }

    // ---- History ----
    let statusFilter = '';
    function setStatusFilter(status) {
        statusFilter = status;
        document.querySelectorAll('#status-filters .chip').forEach(c => c.classList.toggle('active', c.dataset.status === status));
        loadHistory(1);
    }

    function loadHistory(page) {
        const body = document.getElementById('history-body');
        body.innerHTML = '<tr><td colspan="6" class="empty">Loading...</td></tr>';

        const params = new URLSearchParams({ page });
        if (statusFilter) params.set('status', statusFilter);

        fetch(historyUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(res => {
                if (!res.success || res.data.length === 0) {
                    body.innerHTML = '<tr><td colspan="6" class="empty">No runs recorded yet.</td></tr>';
                    document.getElementById('history-pagination').innerHTML = '';
                    return;
                }
                body.innerHTML = res.data.map(run => `
                    <tr>
                        <td><div class="mono" style="font-size:12.5px;">${escapeHtml(run.command)}</div></td>
                        <td><span class="badge badge-${run.status}"><span class="dot"></span>${run.status}</span></td>
                        <td>${run.trigger}</td>
                        <td>${run.started_at ? new Date(run.started_at).toLocaleString() : '—'}</td>
                        <td>${run.duration_ms !== null ? run.duration_ms + ' ms' : '—'}</td>
                        <td>${run.output || run.error ? `<button class="btn" onclick='openModal(${JSON.stringify(run.command)}, ${JSON.stringify(run.error || run.output || '')}, ${run.status === 'success'})'>View</button>` : ''}</td>
                    </tr>
                `).join('');

                const pag = document.getElementById('history-pagination');
                pag.innerHTML = '';
                if (res.last_page > 1) {
                    for (let p = 1; p <= res.last_page; p++) {
                        const b = document.createElement('button');
                        b.className = 'btn' + (p === res.current_page ? ' btn-primary' : '');
                        b.textContent = p;
                        b.onclick = () => loadHistory(p);
                        pag.appendChild(b);
                    }
                }
            });
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
</script>
</body>
</html>
