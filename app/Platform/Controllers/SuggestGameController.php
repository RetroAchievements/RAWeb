<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Community\Enums\UserGameListType;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAlternative;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Services\GameListService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class SuggestGameController extends Controller
{
    public function __construct(
        protected GameListService $gameListService,
    ) {
    }

    private array $gameProgress = [];
    private array $masteredGames = [];
    private array $beatenGames = [];

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }

        $selectedGames = [];
        $gameIds = [];

        $wantToPlayList = UserGameListEntry::where('user_id', $user->id)
            ->where('type', UserGameListType::Play)
            ->join('GameData', 'GameData.ID', '=', 'SetRequest.GameID')
            ->where('GameData.achievements_published', '>', 0)
            ->pluck('GameID')
            ->toArray();

        $this->initializeUserProgress($user);

        $revisedGames = PlayerBadge::where('User', $user->User)
            ->where('AwardType', AwardType::Mastery)
            ->whereNotIn('AwardData', $this->masteredGames)
            ->join('GameData', 'GameData.ID', '=', 'SiteAwards.AwardData')
            ->where('GameData.achievements_published', '>', 0)
            ->whereNotIn('GameData.ConsoleID', [100, 101])
            ->pluck('AwardData')
            ->toArray();

        $wantToPlayChance = (int) ((count($wantToPlayList) + 7) / 8);
        $relatedToMasteryChance = (int) ((count($this->masteredGames) + 1) / 2);
        $relatedToBeatenChance = (int) ((count($this->beatenGames) + 3) / 4);
        $revisedChance = (int) sqrt(count($revisedGames));
        $relatedChance = $wantToPlayChance + $relatedToMasteryChance + $relatedToBeatenChance + $revisedChance;
        $randomChance = (int) (($relatedChance + 3) / 8);
        $totalChance = $relatedChance + $randomChance;

        for ($i = 0; $i < 30; $i++) {
            $randomValue = rand(0, $totalChance);
            if ($randomValue < $wantToPlayChance) {
                $gameId = $wantToPlayList[array_rand($wantToPlayList)];
                if (rand(0, 10) < 2) {
                    // small chance to recommend something related to the Want-to-play
                    // game instead of the Want-to-play game itself.
                    [$gameId, $why] = $this->selectRelatedGame($user, $gameId);
                    $why['game-type'] = 'Want to Play';
                } else {
                    $why = ['how' => 'want-to-play'];
                }
            } elseif ($randomValue < $wantToPlayChance + $relatedToMasteryChance) {
                $gameId = $this->masteredGames[array_rand($this->masteredGames)];
                $gameProgress = $this->gameListService->userProgress[$gameId] ?? [];

                [$gameId, $why] = $this->selectRelatedGame($user, $gameId);

                $userHardcoreProgress = $gameProgress['achievements_unlocked_hardcore'] ?? 0;
                $userSoftcoreProgress = $gameProgress['achievements_unlocked'] ?? 0;
                if ($userHardcoreProgress === $userSoftcoreProgress) {
                    $why['game-type'] = 'mastered';
                } else {
                    $why['game-type'] = 'completed';
                }
            } elseif ($randomValue < $wantToPlayChance + $relatedToMasteryChance + $relatedToBeatenChance) {
                $gameId = $this->beatenGames[array_rand($this->beatenGames)];
                [$gameId, $why] = $this->selectRelatedGame($user, $gameId);
                $why['game-type'] = 'beaten';
            } elseif ($randomValue < $wantToPlayChance + $relatedToMasteryChance + $relatedToBeatenChance + $revisedChance) {
                $gameId = $revisedGames[array_rand($revisedGames)];
                $why = ['how' => 'revised'];
            } else {
                $gameId = $this->selectRandomGameWithAchievements();
                $why = ['how' => 'random'];
            }

            if ($this->isGameEligible($gameId, $gameIds, $why)) {
                $selectedGames[$gameId] = $why;

                $gameIds[] = $gameId;
                if (count($gameIds) === 10) {
                    break;
                }
            }
        }

        while (count($gameIds) < 10) {
            $gameId = $this->selectRandomGameWithAchievements();
            if ($this->isGameEligible($gameId, $gameIds)) {
                $gameIds[] = $gameId;
                $selectedGames[$gameId] = [
                    'how' => 'random',
                ];
            }
        }

        $this->gameListService->initializeGameList($gameIds);

        $this->mergeRelatedGameInfo($selectedGames);

        $this->gameListService->mergeWantToPlay($user);
        $this->gameListService->sortGameList('title');

        return view('pages.games.suggest', [
            'user' => $user,
            'consoles' => $this->gameListService->consoles,
            'games' => $this->gameListService->games,
            'columns' => $this->getColumns(),
            'noGamesMessage' => 'No suggestions available.',
        ]);
    }

    public function forGame(Request $request, Game $game): View
    {
        $user = $request->user();

        $selectedGames = [];
        $gameIds = [];

        $this->initializeUserProgress($user);

        for ($i = 0; $i < 30; $i++) {
            [$gameId, $why] = $this->selectRelatedGame($user, $game->id);

            if ($this->isGameEligible($gameId, $gameIds, $why)) {
                $selectedGames[$gameId] = $why;

                $gameIds[] = $gameId;
                if (count($gameIds) === 10) {
                    break;
                }
            }
        }

        $this->gameListService->initializeGameList($gameIds);

        $this->mergeRelatedGameInfo($selectedGames, showRelatedGames: false);

        $this->gameListService->mergeWantToPlay($user);
        $this->gameListService->sortGameList('title');

        return view('pages.game.[game].suggest', [
            'game' => $game,
            'user' => $user,
            'consoles' => $this->gameListService->consoles,
            'games' => $this->gameListService->games,
            'columns' => $this->getColumns(),
            'noGamesMessage' => 'No suggestions available.',
        ]);
    }

    private function initializeUserProgress(?User $user): void
    {
        if (!$user) {
            return;
        }

        $history = PlayerGame::where('user_id', $user->id)
            ->where('achievements_unlocked', '>', 0)
            ->join('GameData', 'GameData.ID', '=', 'player_games.game_id')
            ->select([
                'game_id',
                'achievements_unlocked',
                'achievements_unlocked_hardcore',
                'achievements_total',
                'beaten_at',
                'ConsoleID',
            ]);

        foreach ($history->get() as $playerGame) {
            if (!System::isGameSystem($playerGame->ConsoleID)) {
                continue;
            }

            $this->gameListService->userProgress[$playerGame->game_id] = [
                'achievements_unlocked' => $playerGame->achievements_unlocked,
                'achievements_unlocked_hardcore' => $playerGame->achievements_unlocked_hardcore,
            ];

            $this->gameProgress[$playerGame->game_id] =
                $playerGame->achievements_unlocked / $playerGame->achievements_total;

            if ($playerGame->achievements_unlocked === $playerGame->achievements_total) {
                $this->masteredGames[] = $playerGame->game_id;
            } elseif ($playerGame->beaten_at) {
                $this->beatenGames[] = $playerGame->game_id;
            }
        }
    }

    private function mergeRelatedGameInfo(array $selectedGames, bool $showRelatedGames = true): void
    {
        foreach ($this->gameListService->games as &$game) {
            $game['SelectionMethod'] = $selectedGames[$game['ID']]['how'];

            if ($showRelatedGames) {
                $relatedGameId = $selectedGames[$game['ID']]['gameId'] ?? 0;
                if ($relatedGameId > 0) {
                    $game['RelatedGame'] = Game::where('ID', $relatedGameId)
                        ->select(['ID', 'Title', 'ImageIcon'])
                        ->first()
                        ->toArray();

                    if (array_key_exists('game-type', $selectedGames[$game['ID']])) {
                        $game['RelatedGameType'] = $selectedGames[$game['ID']]['game-type'];
                    }
                }
            }

            if (array_key_exists('hub', $selectedGames[$game['ID']])) {
                $game['RelatedHub'] = $selectedGames[$game['ID']]['hub'];
            }
        }
    }

    private function getColumns(): array
    {
        // take the default columns and insert the reason column before the progress/backlog columns
        $defaultColumns = $this->gameListService->getColumns();

        $columns = [];
        $columns['title'] = $defaultColumns['title'];
        $columns['achievements'] = $defaultColumns['achievements'];
        $columns['points'] = $defaultColumns['points'];
        $columns['players'] = $defaultColumns['players'];
        $columns['reasoning'] = $this->getReasonColumn();

        if (array_key_exists('progress', $defaultColumns)) {
            $columns['progress'] = $defaultColumns['progress'];
            $columns['backlog'] = $defaultColumns['backlog'];
        }

        return $columns;
    }

    private function getReasonColumn(): array
    {
        return [
            'header' => 'Reasoning',
            'width' => 28,
            'tooltip' => 'Why the game was suggested',
            'render' => function ($game) {
                echo '<td>';
                echo Blade::render('
                    <x-game-list-item.suggest-reason
                        :selectionMethod="$selectionMethod"
                        :relatedSubject="$relatedSubject"
                        :relatedGameId="$relatedGameId"
                        :relatedGameType="$relatedGameType"
                        :relatedGameTitle="$relatedGameTitle"
                        :relatedGameIcon="$relatedGameIcon"
                    />', [
                        'selectionMethod' => $game['SelectionMethod'],
                        'relatedSubject' => $game['RelatedHub'] ?? '',
                        'relatedGameId' => $game['RelatedGame']['ID'] ?? 0,
                        'relatedGameType' => $game['RelatedGameType'] ?? '',
                        'relatedGameTitle' => $game['RelatedGame']['Title'] ?? '',
                        'relatedGameIcon' => $game['RelatedGame']['ImageIcon'] ?? '',
                    ]);
                echo '</td>';
            },
        ];
    }

    public function isGameEligible(int $gameId, array $gameIds, array $why = []): bool
    {
        if ($gameId === 0) {
            /* no game was selected */
            return false;
        }

        if (in_array($gameId, $gameIds)) {
            /* already in list */
            return false;
        }

        $progress = $this->gameProgress[$gameId] ?? 0.0;
        if ($progress > 0.0) {
            /* player has played this before. lower chance to recommend based on how
               much the user has already completed */
            if ($progress >= 1.0 || rand(1, 100) - 10 < $progress * 100) {
                return false;
            }
        }

        // ignore subsets unless they're from the user's want to play list or have
        // been picked via similar-to the base game or another subset
        $how = $why['how'] ?? '';
        if ($how !== 'want-to-play' && $how !== 'similar-to') {
            $title = Game::find($gameId, ['Title'])->Title;
            if (mb_strpos($title, '[Subset') !== false) {
                return false;
            }
        }

        return true;
    }

    public function selectRandomGameWithAchievements(): int
    {
        $games = Game::inRandomOrder()
            ->where('achievements_published', '>', 0)
            ->select('ID');

        return $games->first()->id;
    }

    public function selectRelatedGame(?User $user, int $gameId): array
    {
        switch (rand(1, 10)) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5: // similar game (50%)
                $similarGameId = $this->selectSimilarGame($gameId);
                if ($similarGameId > 0) {
                    return [$similarGameId, [
                        'how' => 'similar-to',
                        'gameId' => $gameId,
                    ]];
                }
                break;

            case 6:
            case 7: // shared hub (20%)
                $relatedHub = GameAlternative::inRandomOrder()
                    ->where('gameID', $gameId)
                    ->join('GameData', 'GameData.ID', '=', 'GameAlternatives.gameIDAlt')
                    ->where('gameIDAlt', '!=', $gameId)
                    ->whereIn('GameData.ConsoleID', [100, 101])
                    ->select(['gameIDAlt', 'Title'])
                    ->first();

                if ($relatedHub !== null && !str_starts_with($relatedHub->Title, '[Meta -')
                        && !str_starts_with($relatedHub->Title, '[Meta|')) {
                    $relatedGameId = $this->selectSimilarGame($relatedHub->gameIDAlt);
                    if ($relatedGameId !== null && $relatedGameId !== $gameId) {
                        return [$relatedGameId, [
                            'how' => 'common-hub',
                            'hub' => $relatedHub->Title,
                            'gameId' => $gameId,
                        ]];
                    }
                }
                break;

            case 8: // same set author (10%)
                $author = Achievement::where('GameID', $gameId)
                    ->where('Flags', '=', AchievementFlag::OfficialCore)
                    ->join('UserAccounts', 'Achievements.user_id', '=', 'UserAccounts.ID')
                    ->groupBy('Achievements.user_id')
                    ->selectRaw('count(*) as Count, UserAccounts.User as Author, Achievements.user_id as AuthorUserID')
                    ->orderBy('Count', 'DESC')
                    ->first();

                if ($author) {
                    $otherAuthoredGame = Achievement::inRandomOrder()
                        ->where('user_id', $author->AuthorUserID)
                        ->where('GameID', '!=', $gameId)
                        ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
                        ->where('GameData.achievements_published', '>', 0)
                        ->where('Achievements.Flags', '=', AchievementFlag::OfficialCore)
                        ->groupBy('GameID')
                        ->select('GameID')
                        ->first();

                    if ($otherAuthoredGame) {
                        return [$otherAuthoredGame->GameID, [
                            'how' => 'common-author',
                            'hub' => $author->Author,
                            'gameId' => $gameId,
                        ]];
                    }
                }
                break;

            case 9:
            case 10: // also mastered by other players (20%)
                // pick five random players, and find the most commonly mastered game among them
                $alternateUsers = PlayerGame::inRandomOrder()
                    ->where('game_id', $gameId)
                    ->whereRaw('achievements_total = achievements_unlocked')
                    ->where('user_id', '!=', $user->id ?? 0)
                    ->limit(5)
                    ->pluck('user_id')
                    ->toArray();

                if (count($alternateUsers) > 0) {
                    $otherMasteredGames = PlayerGame::whereIn('user_id', $alternateUsers)
                        ->whereRaw('achievements_total = achievements_unlocked')
                        ->where('achievements_total', '>', 0)
                        ->where('game_id', '!=', $gameId)
                        ->selectRaw('count(*) as Count, game_id')
                        ->groupBy('game_id')
                        ->orderBy('Count', 'DESC')
                        ->limit(10);

                    $othergameIds = [];
                    $maxCount = 0;
                    foreach ($otherMasteredGames->get() as $otherMasteredGame) {
                        if ($maxCount === 0) {
                            $maxCount = $otherMasteredGame->Count;
                        } elseif ($otherMasteredGame->Count < $maxCount) {
                            break;
                        }
                        $othergameIds[] = $otherMasteredGame->game_id;
                    }

                    if (count($othergameIds) > 0) {
                        return [$othergameIds[array_rand($othergameIds)], [
                            'how' => 'common-player',
                            'gameId' => $gameId,
                        ]];
                    }
                }
                break;
        }

        return [0, []];
    }

    public function selectSimilarGame(int $gameId): int
    {
        $relatedGame = GameAlternative::inRandomOrder()
            ->where('gameID', $gameId)
            ->join('GameData', 'GameData.ID', '=', 'GameAlternatives.gameIDAlt')
            ->whereNotIn('GameData.ConsoleID', [100, 101])
            ->where('GameData.achievements_published', '>', 0)
            ->select('gameIDAlt')
            ->first();

        if ($relatedGame !== null) {
            return $relatedGame->gameIDAlt;
        }

        return 0;
    }
}
