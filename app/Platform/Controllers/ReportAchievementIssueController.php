<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\TicketType;
use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\PlayerSession;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\DetermineTicketCreationBlockReasonAction;
use App\Platform\Data\AchievementData;
use App\Platform\Data\ReportAchievementIssuePagePropsData;
use App\Platform\Services\UserAgentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReportAchievementIssueController extends Controller
{
    public function index(
        Request $request,
        Achievement $achievement,
        DetermineTicketCreationBlockReasonAction $determineTicketCreationBlockReason,
    ): InertiaResponse {
        $this->authorize('view', $achievement);
        $this->authorize('viewAny', Ticket::class);
        $this->authorize('create', Ticket::class);

        /** @var User $user */
        $user = Auth::user();

        $allPlayerAchievements = $user->playerAchievements()->forGame($achievement->game)->get();
        $foundPlayerAchievement = $allPlayerAchievements->where('achievement_id', $achievement->id)->first();

        $achievementData = AchievementData::fromAchievement(
            $achievement,
            $foundPlayerAchievement
        )->include(
            'unlockedAt',
            'unlockedHardcoreAt',
            'game',
            'game.isSubsetGame',
            'game.system',
        );

        $hasSession = $foundPlayerAchievement !== null || $user->hasPlayedGameForAchievement($achievement);
        $blockReason = $determineTicketCreationBlockReason->execute($user, $achievement, $hasSession);

        $can = UserPermissionsData::fromUser($user, triggerable: $achievement)->include('createTicket');
        $can->createTicket = $blockReason === null; // intentional shadowing

        $props = new ReportAchievementIssuePagePropsData(
            achievement: $achievementData,
            hasSession: $hasSession,
            ticketType: $this->determineTicketType($foundPlayerAchievement, $allPlayerAchievements),
            extra: $request->input('extra'),
            can: $can,
            ticketBlockReason: $blockReason,
            hasCasualUnlockFromRestrictedClient: $this->getHasCasualUnlockFromRestrictedClient($foundPlayerAchievement, $achievement),
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
    private function determineTicketType(?PlayerAchievement $playerAchievement, Collection $allPlayerAchievements): TicketType
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

    /**
     * Hardcore unlocks from restricted emulators/cores are automatically
     * demoted to casual. We don't want to offer the manual unlock option
     * for users who find themselves in this circumstance.
     */
    private function getHasCasualUnlockFromRestrictedClient(
        ?PlayerAchievement $playerAchievement,
        Achievement $achievement,
    ): bool {
        if (
            !$playerAchievement?->unlocked_at
            || $playerAchievement->unlocked_hardcore_at
            || !$playerAchievement->player_session_id
        ) {
            return false;
        }

        $userAgentService = new UserAgentService();

        $unlockUserAgent = PlayerSession::whereKey($playerAchievement->player_session_id)->value('user_agent');
        if (!$unlockUserAgent || $userAgentService->getSupportLevel($unlockUserAgent)->allowsHardcoreUnlocks()) {
            return false;
        }

        // Some players have thousands of sessions per game.
        // Only check their newest sessions.
        foreach ($achievement->getRelatedGameIds() as $gameId) {
            $laterUserAgents = PlayerSession::where('user_id', $playerAchievement->user_id)
                ->where('game_id', $gameId)
                ->where('rich_presence_updated_at', '>=', $playerAchievement->unlocked_at)
                ->orderByDesc('rich_presence_updated_at')
                ->limit(100)
                ->pluck('user_agent')
                ->filter()
                ->unique();

            foreach ($laterUserAgents as $userAgent) {
                if ($userAgentService->getSupportLevel($userAgent)->allowsHardcoreUnlocks()) {
                    return false;
                }
            }
        }

        return true;
    }
}
