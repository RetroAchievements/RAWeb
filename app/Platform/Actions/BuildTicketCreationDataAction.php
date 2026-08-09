<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\EmulatorUserAgent;
use App\Models\PlayerAchievement;
use App\Models\PlayerSession;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Data\CreateAchievementTicketPagePropsData;
use App\Platform\Data\EmulatorData;
use App\Platform\Data\GameHashData;
use App\Platform\Enums\UnlockMode;
use App\Platform\Services\UserAgentService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BuildTicketCreationDataAction
{
    /**
     * Sessions shorter than this are treated as a load-and-quit rather than real play, so they do
     * not stand in for what the reporter saw. `player_sessions.duration` is stored in minutes.
     */
    private const MINIMUM_QUALIFYING_SESSION_MINUTES = 5;

    public function __construct(
        private readonly UserAgentService $userAgentService,
    ) {
    }

    public function execute(Achievement $achievement, User $user): CreateAchievementTicketPagePropsData
    {
        $props = CreateAchievementTicketPagePropsData::fromAchievement($achievement);

        $this->addSessionRelatedMultisetHashes($props, $achievement, $user);

        $sessionGameIds = $achievement->getRelatedGameIds();
        $playerAchievement = $user->playerAchievements()
            ->where('achievement_id', $achievement->id)
            ->first();

        $playerSession = $this->findRelevantSession($playerAchievement, $user, $sessionGameIds);
        if ($playerSession === null) {
            // If there's no session data, check if the user has any hardcore sessions.
            $hasHardcoreSession = $user->playerSessions()
                ->whereIn('game_id', $sessionGameIds)
                ->whereHardcore(true)
                ->exists();

            if ($hasHardcoreSession) {
                $props->selectedMode = UnlockMode::Hardcore;
            }

            // An unlock can still name an older trigger even when no session row resolves, and
            // that comparison is exact, so it must not be skipped along with the session data.
            $props->didLogicChangeSinceLastPlayed = $this->getDidLogicChangeSinceLastPlayed(
                $achievement,
                $user,
                $sessionGameIds,
                $playerAchievement,
                null,
            );

            return $props;
        }

        // The unlock's own session, when we found it, is what the reporter earned the achievement
        // in. Any other session is a later visit and says nothing about how they unlocked it.
        $isUnlockSession = $playerSession->id === $playerAchievement?->player_session_id;

        $props->selectedGameHashId = $playerSession->gameHash?->id;

        $props->didLogicChangeSinceLastPlayed = $this->getDidLogicChangeSinceLastPlayed(
            $achievement,
            $user,
            $sessionGameIds,
            $playerAchievement,
            $playerSession,
        );

        if ($playerSession->user_agent) {
            $decoded = $this->userAgentService->decode($playerSession->user_agent);
            $emulatorUserAgent = EmulatorUserAgent::firstWhere('client', $decoded['client']);
            $props->selectedEmulator = $emulatorUserAgent?->emulator->name ?? $decoded['client'];
            $props->emulatorVersion = $decoded['clientVersion'];
            $props->emulatorCore = $decoded['clientVariation'] ?? null;
        }

        $this->addInactiveEmulators($props->emulators, $user, $sessionGameIds);

        // Set the unlock mode based on hardcore unlock or session preference.
        if ($isUnlockSession && $playerAchievement?->unlocked_hardcore_at) {
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
            ->where('duration', '>=', self::MINIMUM_QUALIFYING_SESSION_MINUTES)
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
     */
    private function findRelevantSession(
        ?PlayerAchievement $playerAchievement,
        User $user,
        array $sessionGameIds,
    ): ?PlayerSession {
        // First, try to find a session where the user has unlocked this achievement.
        if ($playerAchievement) {
            $playerSession = $user->playerSessions()
                ->firstWhere('player_sessions.id', $playerAchievement->player_session_id);

            if ($playerSession) {
                return $playerSession;
            }
        }

        // If no unlock was found, find the player's most recent session.
        return $user->playerSessions()
            ->whereIn('game_id', $sessionGameIds)
            ->where('duration', '>=', self::MINIMUM_QUALIFYING_SESSION_MINUTES)
            ->orderByDesc('updated_at')
            ->first();
    }

    /**
     * An emulator fetches achievement logic once, when the game loads. A player can then play for
     * hours, or file a ticket days later, describing behavior the developer has already patched.
     *
     * Prefer the trigger the unlock was earned against, which is exact. Fall back to comparing the
     * current trigger's creation time against the last time we can show the player had the game
     * open.
     *
     * @param int[] $sessionGameIds
     */
    private function getDidLogicChangeSinceLastPlayed(
        Achievement $achievement,
        User $user,
        array $sessionGameIds,
        ?PlayerAchievement $playerAchievement,
        ?PlayerSession $anchorSession,
    ): bool {
        $achievement->loadMissing('currentTrigger');
        $currentTrigger = $achievement->currentTrigger;
        if (!$currentTrigger) {
            return false;
        }

        if ($playerAchievement?->trigger_id) {
            return $playerAchievement->trigger_id !== $currentTrigger->id;
        }

        // The cutoff guards the timestamp comparison only. Two different trigger IDs prove a real
        // revision whenever they were written, so the branch above is deliberately not gated.
        if (!$currentTrigger->created_at || $currentTrigger->created_at->lt(Trigger::VERSIONING_CUTOFF)) {
            return false;
        }

        $lastPlayedAt = $this->resolveLastPlayedAt($user, $sessionGameIds, $anchorSession);
        if (!$lastPlayedAt) {
            return false;
        }

        return $currentTrigger->created_at->gt($lastPlayedAt);
    }

    /**
     * The most recent moment we can show the player had the game open.
     *
     * A reporter who has loaded the game since a revision already has the new logic, so the
     * newest qualifying session wins over the session that anchors the rest of the form. Without
     * this, every unlock that predates trigger recording would warn its owner on replay.
     *
     * @param int[] $sessionGameIds
     */
    private function resolveLastPlayedAt(
        User $user,
        array $sessionGameIds,
        ?PlayerSession $anchorSession,
    ): ?CarbonInterface {
        $lastPlayedAt = $anchorSession?->created_at;

        $mostRecentStart = $user->playerSessions()
            ->whereIn('game_id', $sessionGameIds)
            ->where('duration', '>=', self::MINIMUM_QUALIFYING_SESSION_MINUTES)
            ->max('created_at');

        if ($mostRecentStart) {
            $mostRecentStart = Carbon::parse($mostRecentStart);

            if (!$lastPlayedAt || $mostRecentStart->gt($lastPlayedAt)) {
                $lastPlayedAt = $mostRecentStart;
            }
        }

        return $lastPlayedAt;
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
            ->where('duration', '>=', self::MINIMUM_QUALIFYING_SESSION_MINUTES)
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
