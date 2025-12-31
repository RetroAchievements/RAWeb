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
