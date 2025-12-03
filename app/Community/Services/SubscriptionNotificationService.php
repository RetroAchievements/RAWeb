<?php

declare(strict_types=1);

namespace App\Community\Services;

use App\Community\Enums\SubscriptionSubjectType;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SubscriptionNotificationService
{
    /**
     * Converts an array of User IDs to User records for users who are eligible to receive emails for the specified preference.
     *
     * @return Collection<int, User>
     */
    public function getEmailTargets(array $userIds, int $userPreference): Collection
    {
        return $this->getEmailTargetsQuery($userIds, $userPreference)->get();
    }

    /**
     * @return Builder<User>
     */
    private function getEmailTargetsQuery(array $userIds, int $userPreference): Builder
    {
        return User::query()
            ->whereIn('ID', $userIds)
            ->whereNotNull('EmailAddress')
            ->where('EmailAddress', '<>', '')
            ->whereRaw('websitePrefs & ? != 0', 1 << $userPreference);
    }

    /**
     * Adds entries to the delayed subscriptions queue for an item.
     */
    public function queueNotifications(array $userIds, SubscriptionSubjectType $subjectType, int $subjectId, int $updateId, int $userPreference): void
    {
        if (empty($userIds)) {
            return;
        }

        // only queue up notifications for people who would receive the email
        $userIds = $this->getEmailTargetsQuery($userIds, $userPreference)->pluck('ID')->toArray();
        if (empty($userIds)) {
            return;
        }

        // if a notification already exists for the user, don't create a new one
        $existingNotificationUserIds = UserDelayedSubscription::query()
            ->whereIn('user_id', $userIds)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->pluck('user_id')
            ->toArray();

        $newUserIds = array_diff($userIds, $existingNotificationUserIds);
        if (!empty($newUserIds)) {
            // queue up new notifications
            UserDelayedSubscription::insert(
                array_map(fn ($id) => [
                    'user_id' => $id,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'first_update_id' => $updateId,
                ], $newUserIds)
            );
        }
    }

    /**
     * Resets/clears the notification for a user's subscription
     */
    public function resetNotification(int $userId, SubscriptionSubjectType $subjectType, int $subjectId): void
    {
        UserDelayedSubscription::query()
            ->where('user_id', $userId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->delete();
    }
}
