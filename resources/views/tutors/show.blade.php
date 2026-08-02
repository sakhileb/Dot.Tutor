<x-app-layout>

<style>
    .panel {
        background:#141416;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 12px;
        padding: 1.75rem;
    }
    .avatar-xl {
        width: 64px; height: 64px; border-radius: 9999px;
        background: #6366f1; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .field-label {
        font-size: 0.7rem; font-weight: 700; color: #71717a;
        text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.4rem; display: block;
    }
    .field-input {
        width: 100%; background: #0f0f11; border: 1px solid rgba(67,70,86,0.4);
        border-radius: 8px; padding: 0.6rem 0.85rem; font-size: 0.85rem; color: #f4f4f5;
    }
    .subject-pill {
        display: inline-block; font-size: 0.65rem; font-weight: 600;
        color: #71717a; background: rgba(67,70,86,0.25);
        border-radius: 9999px; padding: 0.15rem 0.55rem; margin: 0 0.3rem 0.3rem 0;
    }
    .submit-btn {
        display:inline-flex;align-items:center;justify-content:center;gap:0.5rem;
        padding:0.7rem 1.25rem;background:linear-gradient(135deg,#6366f1,#4f46e5);
        border-radius:9999px;font-family:'Syne',sans-serif;font-size:0.8rem;font-weight:700;
        color:#fff;border:none;cursor:pointer;width:100%;
    }
    .error-text { color: #f87171; font-size: 0.72rem; margin-top: 0.3rem; }
</style>

<div style="padding: 2rem 2.5rem; max-width: 1000px;">

    <a href="{{ route('tutors.browse') }}" style="font-size:0.78rem;color:#71717a;text-decoration:none;display:inline-block;margin-bottom:1rem;">
        &larr; Back to tutors
    </a>

    <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:1.25rem;align-items:start;">

        <div class="panel">
            <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                <div class="avatar-xl">{{ strtoupper(substr(optional($tutorProfile->user)->name ?? '?', 0, 1)) }}</div>
                <div>
                    <div style="font-family:'Syne',sans-serif;font-size:1.15rem;font-weight:800;color:#f4f4f5;">
                        {{ optional($tutorProfile->user)->name ?? 'Unnamed tutor' }}
                    </div>
                    <div style="font-size:0.85rem;color:#a5b4fc;font-weight:700;">
                        ${{ number_format($tutorProfile->hourly_rate, 2) }}/hr &middot; {{ number_format($tutorProfile->rating, 1) }}&#9733; &middot; {{ $tutorProfile->total_sessions }} sessions taught
                    </div>
                </div>
            </div>

            @if($tutorProfile->bio)
            <p style="font-size:0.85rem;color:#a1a1aa;line-height:1.6;margin-bottom:1rem;">
                {{ $tutorProfile->bio }}
            </p>
            @endif

            <div>
                @foreach($tutorProfile->subjects as $subject)
                <span class="subject-pill">{{ $subject->name }} &middot; {{ ucfirst(str_replace('_',' ',$subject->level)) }}</span>
                @endforeach
            </div>
        </div>

        <div class="panel">
            <div style="font-family:'Syne',sans-serif;font-size:0.95rem;font-weight:700;color:#f4f4f5;margin-bottom:1rem;">
                Book a session
            </div>

            @if(auth()->id() === $tutorProfile->user_id)
                <p style="font-size:0.8rem;color:#71717a;">This is your own tutor profile — you can't book yourself.</p>
            @else
                <form method="POST" action="{{ route('tutors.sessions.store', $tutorProfile) }}">
                    @csrf

                    <div style="margin-bottom:1rem;">
                        <label class="field-label" for="subject_id">Subject</label>
                        <select name="subject_id" id="subject_id" class="field-input">
                            @foreach($tutorProfile->subjects as $subject)
                            <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                        @error('subject_id')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:1rem;">
                        <label class="field-label" for="starts_at">Date &amp; time</label>
                        <input type="datetime-local" name="starts_at" id="starts_at" class="field-input" value="{{ old('starts_at') }}">
                        @error('starts_at')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:1rem;">
                        <label class="field-label" for="duration_minutes">Duration</label>
                        <select name="duration_minutes" id="duration_minutes" class="field-input">
                            <option value="30" @selected(old('duration_minutes') == 30)>30 minutes</option>
                            <option value="60" @selected(old('duration_minutes', 60) == 60)>60 minutes</option>
                            <option value="90" @selected(old('duration_minutes') == 90)>90 minutes</option>
                            <option value="120" @selected(old('duration_minutes') == 120)>120 minutes</option>
                        </select>
                        @error('duration_minutes')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:1rem;">
                        <label class="field-label" for="delivery">Delivery</label>
                        <select name="delivery" id="delivery" class="field-input">
                            <option value="online" @selected(old('delivery', 'online') == 'online')>Online</option>
                            <option value="in_person" @selected(old('delivery') == 'in_person')>In person</option>
                        </select>
                        @error('delivery')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom:1.25rem;">
                        <label class="field-label" for="notes">Notes (optional)</label>
                        <textarea name="notes" id="notes" rows="3" class="field-input">{{ old('notes') }}</textarea>
                        @error('notes')<div class="error-text">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="submit-btn">Request session</button>
                </form>
            @endif
        </div>

    </div>

</div>

</x-app-layout>
