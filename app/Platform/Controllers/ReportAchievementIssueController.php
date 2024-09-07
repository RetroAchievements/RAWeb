<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TicketType;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\ReportAchievementIssuePagePropsData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportAchievementIssueController extends Controller
{
    public function index(Request $request, Achievement $achievement): InertiaResponse
    {
        $this->authorize('view', $achievement);

        /** @var User $user */
        $user = Auth::user();

        $allPlayerAchievements = $user->playerAchievements()->forGame($achievement->game)->get();
        $foundPlayerAchievement = $allPlayerAchievements->where('achievement_id', $achievement->id)->first();

        $achievementData = AchievementData::fromAchievement(
            $achievement,
            $foundPlayerAchievement
        )->include(
            'badgeUnlockedUrl',
            'badgeLockedUrl',
            'unlockedAt',
            'unlockedHardcoreAt',
            'game',
            'game.system',
        );

        $props = new ReportAchievementIssuePagePropsData(
            achievement: $achievementData,
            hasSession: $foundPlayerAchievement ? true : $user->hasPlayed($achievement->game),
            ticketType: $this->determineTicketType($foundPlayerAchievement, $allPlayerAchievements),
            extra: $request->input('extra'),
        );

        return Inertia::render('achievement/[achievement]/report-issue', $props);
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function show(User $user): void
    {
    }

    public function edit(User $user): void
    {
    }

    public function update(Request $request, User $user): void
    {
    }

    public function destroy(User $user): void
    {
    }

    /**
     * @param Collection<int, PlayerAchievement> $allPlayerAchievements
     */
    private function determineTicketType(?PlayerAchievement $playerAchievement, Collection $allPlayerAchievements): int
    {
        $ticketType = TicketType::DidNotTrigger;

        $hasAnyHardcoreUnlocks = $allPlayerAchievements->contains(function ($playerAchievement) {
            return $playerAchievement->unlocked_hardcore_at !== null;
        });

        $unlockedAt = $playerAchievement?->unlocked_at;
        $unlockedHardcoreAt = $playerAchievement?->unlocked_hardcore_at;

        if ($unlockedHardcoreAt || ($unlockedAt && !$hasAnyHardcoreUnlocks)) {
            $ticketType = TicketType::TriggeredAtWrongTime;
        }

        return $ticketType;
    }
}
