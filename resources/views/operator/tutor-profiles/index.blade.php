<x-app-layout>

<style>
    .review-card {
        background:#141416;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    .review-actions { display: flex; gap: 0.75rem; align-items: flex-start; margin-top: 1rem; }
    .btn-approve, .btn-reject {
        border-radius: 8px; padding: 0.5rem 1rem; font-size: 0.8rem; font-weight: 700;
        border: none; cursor: pointer; color: #fff;
    }
    .btn-approve { background: #22c55e; }
    .btn-reject { background: #ef4444; }
    .reject-reason {
        flex: 1; border-radius: 8px; padding: 0.5rem 0.75rem; font-size: 0.8rem;
        background: #1e1e22; border: 1px solid rgba(255,255,255,0.1); color: #f4f4f5;
    }
</style>

<div style="padding:2rem 2.5rem;max-width:900px;margin:0 auto;">
    <h1 style="font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:800;color:#dae2fd;margin:0 0 1.5rem;">
        Tutor Profile Review Queue
    </h1>

    @if(session('status'))
        <div style="margin-bottom:1.5rem;padding:0.75rem 1rem;border-radius:8px;background:rgba(34,197,94,0.1);color:#4ade80;font-size:0.85rem;">
            {{ session('status') }}
        </div>
    @endif

    @if($pendingProfiles->isEmpty())
        <p style="color:#8d90a2;font-size:0.85rem;">No tutor profiles awaiting review.</p>
    @endif

    @foreach($pendingProfiles as $profile)
        <div class="review-card">
            <p style="color:#dae2fd;font-weight:700;margin:0 0 0.25rem;">{{ $profile->user->name }}</p>
            <p style="color:#8d90a2;font-size:0.8rem;margin:0 0 0.5rem;">
                ${{ $profile->hourly_rate }}/hr &middot;
                {{ $profile->subjects->pluck('name')->join(', ') ?: 'No subjects listed' }}
            </p>
            @if($profile->bio)
                <p style="color:#c1c4d6;font-size:0.85rem;margin:0;">{{ $profile->bio }}</p>
            @endif

            <div class="review-actions">
                <form method="POST" action="{{ route('operator.tutor-profiles.approve', $profile) }}">
                    @csrf
                    <button type="submit" class="btn-approve">Approve</button>
                </form>
                <form method="POST" action="{{ route('operator.tutor-profiles.reject', $profile) }}" style="flex:1;display:flex;gap:0.5rem;">
                    @csrf
                    <input type="text" name="reason" class="reject-reason" placeholder="Reason for rejecting" />
                    <button type="submit" class="btn-reject">Reject</button>
                </form>
            </div>
        </div>
    @endforeach
</div>

</x-app-layout>
