<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions;

use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Data\GameSuggestionData;
use App\Platform\Services\GameSuggestions\Enums\SourceGameKind;
use Illuminate\Support\Collection;

class GameSuggestionEngine
{
    /** @var array<array{0: Strategies\GameSuggestionStrategy, 1: int}> */
    private array $strategies;
    private bool $unsafe_useFixedStrategyForTesting = false;

    public function __construct(
        private readonly User $user,
        private readonly ?Game $sourceGame = null,
    ) {
        $this->initializeStrategies($user);
    }

    /**
     * For testing purposes only.
     */
    public function dangerouslyEnableFixedStrategyForTesting(): void
    {
        $this->unsafe_useFixedStrategyForTesting = true;
    }

    private function initializeStrategies(User $user): void
    {
        // Strategies and games will be picked at random, but we can assign weights to our strategies.
        // [strategy, weight]

        if ($this->sourceGame) {
            $this->strategies = [
                [new Strategies\SimilarGameStrategy($this->sourceGame, attachContext: false), 50],
                [new Strategies\SharedHubStrategy($this->sourceGame), 20],
                [new Strategies\CommonPlayersStrategy($this->user, $this->sourceGame, attachContext: false), 20],
                [new Strategies\SharedAuthorStrategy($this->sourceGame, null, attachSourceGame: false), 10],
            ];
        } else {
            $masteredGames = Game::query()
                ->whereHas('playerGames', function ($query) {
                    $query->whereUserId($this->user->id)
                        ->whereColumn('achievements_unlocked', 'achievements_total')
                        ->where('achievements_total', '>', 0);
                })
                ->limit(800)
                ->get();

            $beatenGames = Game::query()
                ->whereHas('playerGames', function ($query) {
                    $query->whereUserId($this->user->id)
                        ->whereNotNull('beaten_at');
                })
                ->limit(800)
                ->get();

            $backlogGameEntries = $this->fastPickRandomBacklogGameEntries($user);

            $this->strategies = [
                [new Strategies\WantToPlayStrategy($this->user), 30],
                [new Strategies\RevisedGameStrategy($this->user), 20],
                [new Strategies\RandomGameStrategy(), 10],
            ];

            // Add strategies based on mastered games.
            foreach ($masteredGames as $masteredGame) {
                $weight = 50 / max(1, count($masteredGames));

                $this->strategies[] = [
                    new Strategies\SimilarGameStrategy($masteredGame, SourceGameKind::Mastered),
                    $weight,
                ];

                $this->strategies[] = [
                    new Strategies\SharedHubStrategy(
                        $masteredGame,
                        sourceGameKind: SourceGameKind::Mastered,
                        attachSourceGame: true,
                    ),
                    $weight * 0.4,
                ];

                $this->strategies[] = [
                    new Strategies\SharedAuthorStrategy($masteredGame, SourceGameKind::Mastered),
                    $weight * 0.2,
                ];

                $this->strategies[] = [new Strategies\CommonPlayersStrategy($this->user, $masteredGame), $weight * 0.4];
            }

            // Add strategies based on beaten games.
            foreach ($beatenGames as $beatenGame) {
                $weight = 25 / max(1, count($beatenGames));

                $this->strategies[] = [
                    new Strategies\SimilarGameStrategy($beatenGame, SourceGameKind::Beaten),
                    $weight,
                ];

                $this->strategies[] = [
                    new Strategies\SharedHubStrategy(
                        $beatenGame,
                        sourceGameKind: SourceGameKind::Beaten,
                        attachSourceGame: true,
                    ),
                    $weight * 0.4,
                ];

                $this->strategies[] = [
                    new Strategies\SharedAuthorStrategy($beatenGame, SourceGameKind::Beaten),
                    $weight * 0.2,
                ];
                $this->strategies[] = [new Strategies\CommonPlayersStrategy($this->user, $beatenGame), $weight * 0.4];
            }

            // Add strategies based on backlog games.
            foreach ($backlogGameEntries as $backlogGameEntry) {
                $weight = 10 / max(1, count($backlogGameEntries));

                $this->strategies[] = [
                    new Strategies\SimilarGameStrategy(
                        $backlogGameEntry->game,
                        sourceGameKind: SourceGameKind::WantToPlay,
                    ),
                ];
            }
        }
    }

    /**
     * @return array<GameSuggestionData>
     */
    public function selectSuggestions(int $limit = 10): array
    {
        // We won't show the user games they already have 100% completion for.
        $masteredGameIds = PlayerGame::whereUserId($this->user->id)
            ->whereAllAchievementsUnlocked()
            ->pluck('game_id')
            ->toArray();

        $suggestions = [];
        $selectedIds = [];
        $attempts = 0;
        $maxAttempts = $limit * 3; // allow some retries for each desired suggestion

        while (count($selectedIds) < $limit && $attempts < $maxAttempts) {
            $strategy = $this->selectWeightedStrategy();

            if ($game = $strategy->select()) {
                $gameId = $game->id;
                if (!in_array($gameId, $selectedIds) && !in_array($gameId, $masteredGameIds)) {
                    $suggestions[] = new GameSuggestionData(
                        gameId: $gameId,
                        reason: $strategy->reason(),
                        context: $strategy->reasonContext(),
                    );

                    $selectedIds[] = $gameId;
                }
            }

            $attempts++;
        }

        return $suggestions;
    }

    /**
     * @return Collection<int, UserGameListEntry>
     */
    private function fastPickRandomBacklogGameEntries(User $user): Collection
    {
        // ->inRandomOrder() is very slow.
        // Instead, we'll pick a random starting point and take a chunk
        // of the user's backlog games. This is random enough.

        $totalCount = $user->gameListEntries()
            ->whereType(UserGameListType::Play)
            ->whereHas('game', function ($query) {
                $query->whereNotIn('ConsoleID', System::getNonGameSystems());
            })
            ->count();

        // Calculate a random offset, ensuring we don't exceed the list bounds.
        $offset = $totalCount > 50 ? random_int(0, $totalCount - 50) : 0;

        return $user->gameListEntries()
            ->whereType(UserGameListType::Play)
            ->whereHas('game', function ($query) {
                $query->whereNotIn('ConsoleID', System::getNonGameSystems());
            })
            ->with('game')
            ->skip($offset)
            ->limit(50)
            ->get();

    }

    private function selectWeightedStrategy(): Strategies\GameSuggestionStrategy
    {
        // In test mode, always return the first strategy.
        if ($this->unsafe_useFixedStrategyForTesting) {
            return $this->strategies[0][0];
        }

        $total = array_sum(array_column($this->strategies, 1));
        $random = random_int(1, (int) $total);
        $current = 0;

        foreach ($this->strategies as [$strategy, $weight]) {
            $current += $weight;
            if ($random <= $current) {
                return $strategy;
            }
        }

        // This should never happen due to how the math works.
        return $this->strategies[0][0];
    }
}
