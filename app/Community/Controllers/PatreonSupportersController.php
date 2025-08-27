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
    public function index(): InertiaResponse
    {
        $baseQuery = PlayerBadge::query()
            ->where('AwardType', AwardType::PatreonSupporter)
            ->join('UserAccounts', 'SiteAwards.user_id', '=', 'UserAccounts.ID')
            ->whereNull('UserAccounts.Deleted')
            ->whereNull('UserAccounts.banned_at')
            ->select('SiteAwards.*', 'UserAccounts.display_name', 'UserAccounts.User')
            ->with('user');

        // Get the 4 most recent supporters.
        $recentSupporters = (clone $baseQuery)
            ->orderBy('SiteAwards.AwardDate', 'desc')
            ->limit(4)
            ->get()
            ->map(fn ($badge) => UserData::fromUser($badge->user)->include('displayName', 'avatarUrl', 'id'))
            ->values();

        // Get all supporters alphabetically.
        $allSupporters = (clone $baseQuery)
            ->orderBy('UserAccounts.display_name', 'asc')
            ->get();

        // Split the supporters into initial and deferred groups.
        $initialSupporters = $allSupporters->take(100);
        $deferredSupporters = $allSupporters->skip(100);

        // Map the results to minimal data objects with only the needed fields for the UI.
        $initialSupportersData = $initialSupporters
            ->map(fn ($badge) => UserData::fromUser($badge->user)->include('displayName', 'avatarUrl', 'id'))
            ->values();

        $deferredSupportersData = $deferredSupporters
            ->map(fn ($badge) => UserData::fromUser($badge->user)->include('displayName', 'avatarUrl', 'id'))
            ->values();

        $props = new PatreonSupportersPagePropsData(
            recentSupporters: $recentSupporters,
            initialSupporters: $initialSupportersData,
            deferredSupporters: Inertia::defer(fn () => $deferredSupportersData),
            totalCount: $allSupporters->count(),
        );

        return Inertia::render('community/patreon-supporters', $props);
    }
}
