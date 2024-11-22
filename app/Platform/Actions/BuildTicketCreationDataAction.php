<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Data\CreateAchievementTicketPagePropsData;
use App\Platform\Enums\UnlockMode;
use App\Platform\Services\UserAgentService;

class BuildTicketCreationDataAction
{
    public function __construct(
        private readonly UserAgentService $userAgentService,
    ) {
    }

    public function execute(Achievement $achievement, User $user): CreateAchievementTicketPagePropsData
    {
        $props = CreateAchievementTicketPagePropsData::fromAchievement($achievement);

        $sessionData = $this->findRelevantSessionData($achievement, $user);
        if ($sessionData === null) {
            // If there's no session data, check if the user has any hardcore unlocks for this game.
            $hasHardcoreSession = $user->playerSessions()
                ->whereGameId($achievement->game->id)
                ->whereHardcore(true)
                ->exists();

            if ($hasHardcoreSession) {
                $props->selectedMode = UnlockMode::Hardcore;
            }

            return $props;
        }

        [$userAgent, $hashId, $playerAchievement] = $sessionData;
        $props->selectedGameHashId = $hashId;

        if ($userAgent) {
            $decoded = $this->userAgentService->decode($userAgent);
            $props->selectedEmulator = $decoded['client'];
            $props->emulatorVersion = $decoded['clientVersion'];
            $props->emulatorCore = $decoded['clientVariation'] ?? null;
        }

        // Set the unlock mode based on hardcore unlock or session preference.
        if ($playerAchievement?->unlocked_hardcore_at) {
            $props->selectedMode = UnlockMode::Hardcore;
        } elseif ($user->playerSessions()->whereGameId($achievement->game->id)->whereHardcore(true)->exists()) {
            $props->selectedMode = UnlockMode::Hardcore;
        }

        return $props;
    }

    /**
     * @return array{string|null, int|null, PlayerAchievement|null}|null
     */
    private function findRelevantSessionData(Achievement $achievement, User $user): ?array
    {
        // First, try to find a session where the user has unlocked this achievement.
        $playerAchievement = $user->playerAchievements()
            ->where('achievement_id', $achievement->id)
            ->first();

        if ($playerAchievement) {
            $playerSession = $user->playerSessions()
                ->firstWhere('player_sessions.id', $playerAchievement->player_session_id);

            if ($playerSession) {
                return [
                    $playerSession->user_agent,
                    $playerSession->gameHash?->id,
                    $playerAchievement,
                ];
            }
        }

        // If no unlock was found, find the player's most relevant session.
        $playerSession = $user->playerSessions()
            ->where('game_id', $achievement->game->id)
            ->where('duration', '>=', 5)
            ->orderByDesc('updated_at')
            ->first();

        if (!$playerSession) {
            return null;
        }

        return [
            $playerSession->user_agent,
            $playerSession->gameHash?->id,
            null,
        ];
    }
}
