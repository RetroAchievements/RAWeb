<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Community\Enums\NewsCategory;
use App\Models\News;
use App\Models\User;

class CheckHasUnreadSiteReleaseNoteAction
{
    /**
     * Check if there's an unread site release note for the user.
     * Returns true only if:
     * - The user is authenticated, AND
     * - There's at least one site release note, AND
     * - The latest release note is <= 48 hours old, AND
     * - The user hasn't viewed it yet.
     */
    public function execute(?User $user): bool
    {
        // Only authenticated users can have an active unread status.
        // If the user is a guest, we don't want to show an unread indicator.
        if (!$user) {
            return false;
        }

        $latestReleaseNote = News::where('category', NewsCategory::SiteReleaseNotes)
            ->orderByDesc('created_at')
            ->first();

        // If no release notes exist, bail.
        if (!$latestReleaseNote) {
            return false;
        }

        // If the release note is 2 days or more old, bail.
        $is48HoursOrLessOld = $latestReleaseNote->created_at->greaterThanOrEqualTo(now()->subHours(48));
        if (!$is48HoursOrLessOld) {
            return false;
        }

        // If we made it this far, we can return whether or not the user has
        // actually seen the latest release note entity.
        return !$latestReleaseNote->wasViewedBy($user);
    }
}
