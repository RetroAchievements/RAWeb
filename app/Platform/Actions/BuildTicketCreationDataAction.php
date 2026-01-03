<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\EmulatorUserAgent;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Data\CreateAchievementTicketPagePropsData;
use App\Platform\Data\EmulatorData;
use App\Platform\Data\GameHashData;
use App\Platform\Enums\UnlockMode;
use App\Platform\Services\UserAgentService;
use Illuminate\Support\Collection;

class BuildTicketCreationDataAction
{
    public function __construct(
        private readonly UserAgentService $userAgentService,
    ) {
    }

    public function execute(Achievement $achievement, User $user): CreateAchievementTicketPagePropsData
    {
        $props = CreateAchievementTicketPagePropsData::fromAchievement($achievement);

        $this->addSessionRelatedMultisetHashes($props, $achievement, $user);

        $sessionGameIds = $achievement->getRelatedGameIds();
        $sessionData = $this->findRelevantSessionData($achievement, $user, $sessionGameIds);
        if ($sessionData === null) {
            // If there's no session data, check if the user has any hardcore sessions.
            $hasHardcoreSession = $user->playerSessions()
                ->whereIn('game_id', $sessionGameIds)
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
            $emulatorUserAgent = EmulatorUserAgent::firstWhere('client', $decoded['client']);
            $props->selectedEmulator = $emulatorUserAgent?->emulator->name ?? $decoded['client'];
            $props->emulatorVersion = $decoded['clientVersion'];
            $props->emulatorCore = $decoded['clientVariation'] ?? null;
        }

        $this->addInactiveEmulators($props->emulators, $user, $sessionGameIds);

        // Set the unlock mode based on hardcore unlock or session preference.
        if ($playerAchievement?->unlocked_hardcore_at) {
            $props->selectedMode = UnlockMode::Hardcore;
        } elseif ($user->playerSessions()->whereIn('game_id', $sessionGameIds)->whereHardcore(true)->exists()) {
            $props->selectedMode = UnlockMode::Hardcore;
        }

        return $props;
    }

    /**
     * @param Collection<int, EmulatorData> $emulators
     * @param int[] $sessionGameIds
     */
    private function addInactiveEmulators(Collection &$emulators, User $user, array $sessionGameIds): void
    {
        $userAgents = $user->playerSessions()
            ->whereIn('game_id', $sessionGameIds)
            ->where('duration', '>=', 5)
            ->select('user_agent')
            ->distinct()
            ->pluck('user_agent');

        $needsOther = false;
        foreach ($userAgents as $userAgent) {
            $decoded = $this->userAgentService->decode($userAgent ?? '');

            if (!$emulators->contains('name', $decoded['client'])) {
                $emulatorUserAgent = EmulatorUserAgent::firstWhere('client', $decoded['client']);
                if (!$emulatorUserAgent) {
                    $needsOther = true;
                } elseif (!$emulators->contains('name', $emulatorUserAgent->emulator->name)) {
                    $emulators->add(EmulatorData::fromEmulator($emulatorUserAgent->emulator));
                }
            }
        }

        if ($needsOther) {
            $emulators->add(new EmulatorData(0, 'Other (please specify in description)'));
        }
    }

    /**
     * @param int[] $sessionGameIds
     * @return array{string|null, int|null, PlayerAchievement|null}|null
     */
    private function findRelevantSessionData(Achievement $achievement, User $user, array $sessionGameIds): ?array
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

        // If no unlock was found, find the player's most recent session.
        $playerSession = $user->playerSessions()
            ->whereIn('game_id', $sessionGameIds)
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

    /**
     * Adds hashes the user has actually used in their sessions,
     * respecting whatever the game's multiset boundaries are.
     */
    private function addSessionRelatedMultisetHashes(
        CreateAchievementTicketPagePropsData $props,
        Achievement $achievement,
        User $user,
    ): void {
        $achievementSet = $achievement->achievementSet;
        if (!$achievementSet) {
            return;
        }

        $allPossibleHashes = (new ResolveAchievementSetGameHashesAction())
            ->execute($achievementSet);

        $existingHashIds = collect($props->gameHashes)->pluck('id')->toArray();

        // Filter to hashes the user has actually used in their own play sessions.
        $userSessionHashIds = $user->playerSessions()
            ->whereIn('game_hash_id', $allPossibleHashes->pluck('id'))
            ->where('duration', '>=', 5)
            ->distinct()
            ->pluck('game_hash_id');

        $additionalHashes = $allPossibleHashes
            ->whereIn('id', $userSessionHashIds)
            ->whereNotIn('id', $existingHashIds);

        // If we have nothing to append to the list of hashes, bail.
        if ($additionalHashes->isEmpty()) {
            return;
        }

        $allHashes = collect($props->gameHashes)
            ->concat(GameHashData::fromCollection($additionalHashes))
            ->sortBy('id')
            ->values()
            ->all();

        $props->gameHashes = $allHashes;
    }
}
