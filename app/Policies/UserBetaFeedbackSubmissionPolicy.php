<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserBetaFeedbackSubmission;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserBetaFeedbackSubmissionPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return false;
    }

    public function viewAny(?User $user): bool
    {
        return false;
    }

    public function view(?User $user, UserBetaFeedbackSubmission $userBetaFeedbackSubmission): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        /**
         * To submit beta feedback, a user:
         * - Must be in good account standing.
         * - Must have their email verified.
         * - Must have been a member for at least 2 weeks.
         * - Must have at least 3,000 points in either mode.
         */
        if ($user->isMuted() || !$user->isEmailVerified()) {
            return false;
        }

        $hasMinimumMembershipDuration = $user->created_at < now()->subWeeks(2);
        $hasMinimumPoints = $user->points_hardcore >= 3000 || $user->points >= 3000;

        return $hasMinimumMembershipDuration && $hasMinimumPoints;
    }

    public function update(User $user, UserBetaFeedbackSubmission $userBetaFeedbackSubmission): bool
    {
        return false;
    }

    public function delete(User $user, UserBetaFeedbackSubmission $userBetaFeedbackSubmission): bool
    {
        return false;
    }

    public function restore(User $user, UserBetaFeedbackSubmission $userBetaFeedbackSubmission): bool
    {
        return false;
    }

    public function forceDelete(User $user, UserBetaFeedbackSubmission $userBetaFeedbackSubmission): bool
    {
        return false;
    }
}
