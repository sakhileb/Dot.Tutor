<?php

namespace App\Policies;

use App\Models\TutorSession;
use App\Models\User;

class TutorSessionPolicy
{
    /**
     * A session is only visible to the two people actually in it — the
     * student who booked it, or the tutor whose profile it's booked
     * against. No admin/staff role exists in this schema to justify a
     * wider view (see routes/web.php's /dashboard scoping for the same rule).
     */
    public function view(User $user, TutorSession $session): bool
    {
        return $session->student_id === $user->id
            || $session->tutorProfile->user_id === $user->id;
    }

    public function cancel(User $user, TutorSession $session): bool
    {
        return $this->view($user, $session)
            && in_array($session->status, ['pending', 'confirmed'], true);
    }
}
