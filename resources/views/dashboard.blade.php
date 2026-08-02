<x-app-layout>

<style>
    .kpi-card {
        background:#141416;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px;
        padding: 1.5rem 1.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
        transition: border-color 0.2s;
    }
    .kpi-card:hover { border-color: rgba(99,102,241,0.35); }
    .kpi-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 0.5rem;
    }
    .kpi-value {
        font-family:'Syne', sans-serif;
        font-size: 2rem; font-weight: 800;
        color:#f4f4f5; line-height: 1;
    }
    .kpi-label {
        font-size: 0.72rem; font-weight: 600;
        color:#71717a; text-transform: uppercase; letter-spacing: 0.1em;
    }
    .kpi-sub { font-size: 0.72rem; color: #6366f1; font-weight: 600; margin-top: 0.2rem; }

    .panel {
        background:#141416;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px;
        overflow: hidden;
    }
    .panel-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        display: flex; align-items: center; gap: 0.6rem;
    }
    .panel-title {
        font-family:'Syne', sans-serif;
        font-size: 0.875rem; font-weight: 700; color:#f4f4f5;
    }
    .panel-count {
        font-size: 0.7rem; font-weight: 700;
        background: rgba(99,102,241,0.15); color: #a5b4fc;
        padding: 0.15rem 0.55rem; border-radius: 9999px;
    }

    .subject-chip {
        background: rgba(20,20,22,0.8);
        border: 1px solid rgba(67,70,86,0.3);
        border-radius: 10px;
        padding: 0.75rem 1rem;
        display: flex; flex-direction: column; gap: 0.25rem;
        transition: border-color 0.2s, background 0.2s;
        cursor: default;
    }
    .subject-chip:hover {
        border-color: rgba(99,102,241,0.4);
        background: rgba(99,102,241,0.07);
    }
    .subject-name { font-family:'Syne',sans-serif; font-size:0.8rem; font-weight:700; color:#f4f4f5; }
    .subject-tutors { font-size:0.68rem; color:#71717a; }

    .session-row {
        display: flex; align-items: center; gap: 1rem;
        padding: 0.9rem 1.5rem;
        border-bottom: 1px solid rgba(67,70,86,0.12);
        transition: background 0.15s;
    }
    .session-row:last-child { border-bottom: none; }
    .session-row:hover { background: rgba(99,102,241,0.04); }

    .avatar-sm {
        width: 32px; height: 32px; border-radius: 9999px;
        background: #6366f1; display: flex; align-items: center; justify-content: center;
        font-size: 0.7rem; font-weight: 700; color: #fff; flex-shrink: 0;
    }

    .status-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.2rem 0.6rem; border-radius: 9999px;
        font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
    }
    .badge-pending    { background: rgba(234,179,8,0.12);  color: #fbbf24; }
    .badge-confirmed  { background: rgba(34,197,94,0.12);  color: #4ade80; }
    .badge-completed  { background: rgba(99,102,241,0.15); color: #a5b4fc; }
    .badge-cancelled  { background: rgba(239,68,68,0.12);  color: #f87171; }

    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th {
        padding: 0.75rem 1.25rem;
        font-size: 0.65rem; font-weight: 700; color:#71717a;
        text-transform: uppercase; letter-spacing: 0.1em;
        text-align: left; border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .data-table td {
        padding: 0.85rem 1.25rem;
        font-size: 0.8rem; color: #b7c8e1;
        border-bottom: 1px solid rgba(67,70,86,0.1);
        vertical-align: middle;
    }
    .data-table tr:last-child td { border-bottom: none; }
    .data-table tr:hover td { background: rgba(99,102,241,0.04); }
    .data-table .tutor-cell { display:flex; align-items:center; gap:0.6rem; }
    .data-table .amount { font-family:'Syne',sans-serif; font-weight:700; color:#a5b4fc; }
    .data-table .dim { color:#71717a; }
</style>

<div style="padding: 2rem 2.5rem; max-width: 1400px;">

    {{-- Page header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;">
        <div>
            <h1 style="font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:#f4f4f5;margin:0 0 0.2rem;">
                Tutor Dashboard
            </h1>
            <p style="font-size:0.8rem;color:#71717a;margin:0;">
                {{ now()->format('l, F j, Y') }}
            </p>
        </div>
        <a href="{{ route('tutors.browse') }}" style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.6rem 1.25rem;background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:9999px;font-family:'Syne',sans-serif;font-size:0.8rem;font-weight:700;color:#fff;text-decoration:none;box-shadow:0 6px 18px rgba(99,102,241,0.3);">
            <span class="material-symbols-rounded" style="font-size:18px;">add_circle</span>
            Book Session
        </a>
    </div>

    {{-- KPI row --}}
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:1rem;margin-bottom:2rem;">

        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(99,102,241,0.12);">
                <span class="material-symbols-rounded" style="font-size:20px;color:#818cf8;">event_note</span>
            </div>
            <div class="kpi-label">Total Sessions</div>
            <div class="kpi-value">{{ number_format($totalSessions) }}</div>
            <div class="kpi-sub">All time</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(234,179,8,0.12);">
                <span class="material-symbols-rounded" style="font-size:20px;color:#fbbf24;">schedule</span>
            </div>
            <div class="kpi-label">Upcoming</div>
            <div class="kpi-value">{{ number_format($upcomingSessions) }}</div>
            <div class="kpi-sub">Pending &amp; confirmed</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(34,197,94,0.12);">
                <span class="material-symbols-rounded" style="font-size:20px;color:#4ade80;">task_alt</span>
            </div>
            <div class="kpi-label">Completed</div>
            <div class="kpi-value">{{ number_format($completedSessions) }}</div>
            <div class="kpi-sub">Finished sessions</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(99,102,241,0.12);">
                <span class="material-symbols-rounded" style="font-size:20px;color:#818cf8;">verified_user</span>
            </div>
            <div class="kpi-label">Verified Tutors</div>
            <div class="kpi-value">{{ number_format($totalTutors) }}</div>
            <div class="kpi-sub">Approved profiles</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(34,197,94,0.12);">
                <span class="material-symbols-rounded" style="font-size:20px;color:#4ade80;">person_check</span>
            </div>
            <div class="kpi-label">Available Now</div>
            <div class="kpi-value">{{ number_format($availableTutors) }}</div>
            <div class="kpi-sub">Active tutors</div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(99,102,241,0.12);">
                <span class="material-symbols-rounded" style="font-size:20px;color:#818cf8;">payments</span>
            </div>
            <div class="kpi-label">Total Revenue</div>
            <div class="kpi-value" style="font-size:1.6rem;">${{ number_format($totalRevenue, 0) }}</div>
            <div class="kpi-sub">Completed sessions</div>
        </div>

    </div>

    {{-- Middle row: Subjects + Upcoming Sessions --}}
    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:1.25rem;margin-bottom:1.25rem;">

        {{-- Subjects grid --}}
        <div class="panel">
            <div class="panel-header">
                <span class="material-symbols-rounded" style="font-size:18px;color:#818cf8;">menu_book</span>
                <span class="panel-title">Subjects</span>
                <span class="panel-count">{{ $subjects->count() }}</span>
            </div>
            <div style="padding:1rem;display:grid;grid-template-columns:repeat(2,1fr);gap:0.6rem;">
                @forelse($subjects as $subject)
                <div class="subject-chip">
                    <div class="subject-name">{{ $subject->name }}</div>
                    <div class="subject-tutors">
                        {{ $subject->tutors_count }} {{ Str::plural('tutor', $subject->tutors_count) }}
                        &middot; {{ ucfirst(str_replace('_',' ',$subject->level)) }}
                    </div>
                </div>
                @empty
                <div style="grid-column:span 2;padding:1.5rem;text-align:center;color:#71717a;font-size:0.8rem;">
                    No subjects found.
                </div>
                @endforelse
            </div>
        </div>

        {{-- Upcoming sessions list --}}
        <div class="panel">
            <div class="panel-header">
                <span class="material-symbols-rounded" style="font-size:18px;color:#fbbf24;">upcoming</span>
                <span class="panel-title">Upcoming Sessions</span>
                <span class="panel-count">{{ $upcomingSessionsList->count() }}</span>
            </div>
            @forelse($upcomingSessionsList as $session)
            <a href="{{ route('sessions.show', $session) }}" class="session-row" style="text-decoration:none;color:inherit;">
                <div class="avatar-sm">
                    {{ strtoupper(substr(optional($session->tutorProfile->user)->name ?? '?', 0, 1)) }}
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.8rem;font-weight:600;color:#f4f4f5;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ optional($session->tutorProfile->user)->name ?? '—' }}
                    </div>
                    <div style="font-size:0.7rem;color:#71717a;">
                        {{ optional($session->subject)->name ?? '—' }}
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:0.75rem;font-weight:600;color:#a1a1aa;">
                        {{ $session->starts_at->format('M j, g:i A') }}
                    </div>
                    <div style="font-size:0.68rem;color:#71717a;">
                        {{ $session->duration_minutes }} min
                    </div>
                </div>
                <div style="flex-shrink:0;">
                    @php $st = $session->status; @endphp
                    <span class="status-badge badge-{{ $st }}">{{ $st }}</span>
                </div>
            </a>
            @empty
            <div style="padding:2.5rem;text-align:center;color:#71717a;font-size:0.8rem;">
                No upcoming sessions scheduled.
            </div>
            @endforelse
        </div>

    </div>

    {{-- Recent Sessions table --}}
    <div class="panel">
        <div class="panel-header">
            <span class="material-symbols-rounded" style="font-size:18px;color:#818cf8;">history</span>
            <span class="panel-title">Recent Sessions</span>
            <span class="panel-count">{{ $recentSessions->count() }}</span>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Tutor</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSessions as $session)
                    @php
                        $tutorName = optional($session->tutorProfile->user)->name ?? '—';
                        $initial   = strtoupper(substr($tutorName, 0, 1));
                        $amount    = $session->rate * $session->duration_minutes / 60;
                        $st        = $session->status;
                    @endphp
                    <tr>
                        <td>
                            <div class="tutor-cell">
                                <div class="avatar-sm">{{ $initial }}</div>
                                <span style="font-weight:600;color:#f4f4f5;">{{ $tutorName }}</span>
                            </div>
                        </td>
                        <td>{{ optional($session->subject)->name ?? '—' }}</td>
                        <td class="dim">{{ $session->starts_at->format('M j, Y') }}</td>
                        <td class="dim">{{ $session->duration_minutes }} min</td>
                        <td><span class="status-badge badge-{{ $st }}">{{ $st }}</span></td>
                        <td style="text-align:right;" class="amount">${{ number_format($amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;padding:2.5rem;color:#71717a;">
                            No sessions recorded yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

</x-app-layout>
