<x-app-layout>

<style>
    .panel {
        background:#141416;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px;
        padding: 1.75rem;
        max-width: 640px;
    }
    .status-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.25rem 0.7rem; border-radius: 9999px;
        font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;
    }
    .badge-pending    { background: rgba(234,179,8,0.12);  color: #fbbf24; }
    .badge-confirmed  { background: rgba(34,197,94,0.12);  color: #4ade80; }
    .badge-completed  { background: rgba(99,102,241,0.15); color: #a5b4fc; }
    .badge-cancelled  { background: rgba(239,68,68,0.12);  color: #f87171; }
    .detail-row { display: flex; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid rgba(67,70,86,0.15); font-size: 0.82rem; }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { color: #71717a; }
    .detail-value { color: #f4f4f5; font-weight: 600; }
    .cancel-btn {
        margin-top: 1.25rem; padding: 0.6rem 1.1rem; border-radius: 9999px;
        background: rgba(239,68,68,0.12); color: #f87171; border: 1px solid rgba(239,68,68,0.25);
        font-size: 0.78rem; font-weight: 700; cursor: pointer;
    }
</style>

<div style="padding: 2rem 2.5rem;">

    <a href="{{ route('dashboard') }}" style="font-size:0.78rem;color:#71717a;text-decoration:none;display:inline-block;margin-bottom:1rem;">
        &larr; Back to dashboard
    </a>

    @if(session('status'))
    <div style="max-width:640px;margin-bottom:1rem;padding:0.75rem 1rem;border-radius:8px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:#4ade80;font-size:0.82rem;">
        {{ session('status') }}
    </div>
    @endif

    <div class="panel">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
            <div style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;color:#f4f4f5;">
                {{ optional($session->subject)->name ?? 'Session' }}
            </div>
            <span class="status-badge badge-{{ $session->status }}">{{ $session->status }}</span>
        </div>

        <div class="detail-row">
            <span class="detail-label">Tutor</span>
            <span class="detail-value">{{ optional($session->tutorProfile->user)->name ?? '—' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Student</span>
            <span class="detail-value">{{ optional($session->student)->name ?? '—' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">When</span>
            <span class="detail-value">{{ $session->starts_at->format('l, M j, Y \a\t g:i A') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Duration</span>
            <span class="detail-value">{{ $session->duration_minutes }} minutes</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Delivery</span>
            <span class="detail-value">{{ ucfirst(str_replace('_',' ',$session->delivery)) }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Amount</span>
            <span class="detail-value">${{ number_format($session->rate * $session->duration_minutes / 60, 2) }}</span>
        </div>

        @if($session->notes)
        <div style="margin-top:1rem;">
            <div class="detail-label" style="margin-bottom:0.3rem;">Notes</div>
            <div style="font-size:0.82rem;color:#a1a1aa;">{{ $session->notes }}</div>
        </div>
        @endif

        @if(in_array($session->status, ['pending', 'confirmed']))
        <form method="POST" action="{{ route('sessions.cancel', $session) }}" onsubmit="return confirm('Cancel this session?');">
            @csrf
            <button type="submit" class="cancel-btn">Cancel session</button>
        </form>
        @endif
    </div>

</div>

</x-app-layout>
