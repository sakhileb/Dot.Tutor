<?php

namespace App\Policies;

use App\Models\LessonResource;
use App\Models\User;

/**
 * A lesson resource's authorization boundary is the two-party session it
 * belongs to (same shape as TutorSessionPolicy::view() — student or tutor,
 * never a third party), not a column on the resource itself.
 */
class LessonResourcePolicy
{
    public function view(User $user, LessonResource $resource): bool
    {
        return $resource->session->student_id === $user->id
            || $resource->session->tutorProfile->user_id === $user->id;
    }

    /**
     * Deleting is narrower than viewing -- only the party who uploaded a
     * resource can remove it, not just anyone in the session. The blade
     * already hides the Remove button from non-uploaders, but that's UI
     * only; this is the actual boundary.
     */
    public function delete(User $user, LessonResource $resource): bool
    {
        return $resource->uploader_id === $user->id;
    }
}
