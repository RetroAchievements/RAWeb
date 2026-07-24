<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Data\PatreonSupportersPagePropsData;
use App\Community\Enums\AwardType;
use App\Data\UserData;
use App\Http\Controller;
use App\Models\PlayerBadge;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PatreonSupportersController extends Controller
{
    /**
     * How many supporters of a given tier ship with the initial page load.
     */
    private const INITIAL_SUPPORTERS_PER_TIER = 100;

    public function index(): InertiaResponse
    {
        $baseQuery = PlayerBadge::query()
            ->where('award_type', AwardType::PatreonSupporter)
            ->join('users', 'user_awards.user_id', '=', 'users.id')
            ->whereNull('users.deleted_at')
            ->whereNull('users.banned_at')
            ->select('user_awards.*', 'users.display_name', 'users.username')
            ->with('user');

        // Get the 4 most recent supporters.
        $recentSupporters = (clone $baseQuery)
            ->orderBy('user_awards.awarded_at', 'desc')
            ->limit(4)
            ->get()
            ->map(fn ($badge) => UserData::fromUser($badge->user)->include('displayName', 'avatarUrl', 'id'))
            ->values();

        // Get all supporters alphabetically.
        $allSupporters = (clone $baseQuery)
            ->orderBy('users.display_name', 'asc')
            ->get();

        $tier2Supporters = $allSupporters->where('award_tier', 2);
        $tier1Supporters = $allSupporters->where('award_tier', '!=', 2);

        // Map the results to minimal data objects with only the needed fields for the UI.
        $toUserData = fn ($badges) => $badges
            ->map(fn ($badge) => UserData::fromUser($badge->user)->include('displayName', 'avatarUrl', 'id'))
            ->values();

        // Both deferred props are in the same group, so they resolve in a single follow-up request.
        $deferredTier2SupportersData = $toUserData($tier2Supporters->skip(self::INITIAL_SUPPORTERS_PER_TIER));
        $deferredTier1SupportersData = $toUserData($tier1Supporters->skip(self::INITIAL_SUPPORTERS_PER_TIER));

        $props = new PatreonSupportersPagePropsData(
            recentSupporters: $recentSupporters,
            initialTier2Supporters: $toUserData($tier2Supporters->take(self::INITIAL_SUPPORTERS_PER_TIER)),
            deferredTier2Supporters: Inertia::defer(fn () => $deferredTier2SupportersData),
            tier2Count: $tier2Supporters->count(),
            initialTier1Supporters: $toUserData($tier1Supporters->take(self::INITIAL_SUPPORTERS_PER_TIER)),
            deferredTier1Supporters: Inertia::defer(fn () => $deferredTier1SupportersData),
            tier1Count: $tier1Supporters->count(),
        );

        return Inertia::render('community/patreon-supporters', $props);
    }
}
