<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Community\Enums\UserGameListType;
use App\Community\Models\UserGameListEntry;
use App\Http\Controller;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\GameAlternative;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\System;
use App\Platform\Services\GameListService;
use App\Site\Models\User;
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

        $randomChance = 40;
        $wantToPlayChance = (int) (count($wantToPlayList) / 8);
        $relatedToMasteryChance = (int) (count($this->masteredGames) / 2);
        $revisedChance = (int) sqrt(count($revisedGames));
        $totalChance = $wantToPlayChance + $relatedToMasteryChance + $revisedChance + $randomChance;

        for ($i = 0; $i < 30; $i++) {
            $randomValue = rand(0, $totalChance);
            if ($randomValue < $wantToPlayChance) {
                $gameId = $wantToPlayList[array_rand($wantToPlayList)];
                $why = ['how' => 'want-to-play'];
            } elseif ($randomValue < $wantToPlayChance + $relatedToMasteryChance) {
                $gameId = $this->masteredGames[array_rand($this->masteredGames)];
                [$gameId, $why] = $this->selectRelatedGame($user, $gameId);
            } elseif ($randomValue < $wantToPlayChance + $relatedToMasteryChance + $revisedChance) {
                $gameId = $revisedGames[array_rand($revisedGames)];
                $why = ['how' => 'revised'];
            } else {
                $gameId = $this->selectRandomGameWithAchievements();
                $why = ['how' => 'random'];
            }

            if ($this->isGameEligible($gameId, $gameIds)) {
                $selectedGames[$gameId] = $why;

                $gameIds[] = $gameId;
                if (count($gameIds) == 10) {
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
        
        return view('platform.components.player.suggest-game-page', [
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

            if ($this->isGameEligible($gameId, $gameIds)) {
                $selectedGames[$gameId] = $why;

                $gameIds[] = $gameId;
                if (count($gameIds) == 10) {
                    break;
                }
            }
        }

        $this->gameListService->initializeGameList($gameIds);

        $this->mergeRelatedGameInfo($selectedGames, showRelatedGames: false);

        $this->gameListService->mergeWantToPlay($user);
        $this->gameListService->sortGameList('title');
       
        return view('platform.components.game.suggest-game-page', [
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

            if ($playerGame->achievements_unlocked == $playerGame->achievements_total) {
                $this->masteredGames[] = $playerGame->game_id;
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
                        :relatedGameTitle="$relatedGameTitle"
                        :relatedGameIcon="$relatedGameIcon"
                    />', [
                        'selectionMethod' => $game['SelectionMethod'],
                        'relatedSubject' => $game['RelatedHub'] ?? '',
                        'relatedGameId' => $game['RelatedGame']['ID'] ?? 0,
                        'relatedGameTitle' => $game['RelatedGame']['Title'] ?? '',
                        'relatedGameIcon' => $game['RelatedGame']['ImageIcon'] ?? '',
                    ]);
                echo '</td>';
            },
        ];
    }

    public function isGameEligible(int $gameId, array $gameIds): bool
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
            if ($progress >= 1.0 || rand(1, 100) < $progress * 100) {
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
                    ->whereIn('GameData.ConsoleID', [100, 101])
                    ->select(['gameIDAlt', 'Title'])
                    ->first();

                if ($relatedHub != null && !str_starts_with($relatedHub->Title, '[Meta -')) {
                    $relatedGameId = $this->selectSimilarGame($relatedHub->gameIDAlt);
                    if ($relatedGameId != null) {
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
                    ->groupBy('Author')
                    ->selectRaw('count(*) as Count, Author')
                    ->orderBy('Count', 'DESC')
                    ->first();

                if ($author) {
                    $otherAuthoredGame = Achievement::inRandomOrder()
                        ->where('Author', $author->Author)
                        ->where('GameID', '!=', $gameId)
                        ->join('GameData', 'GameData.ID', '=', 'Achievements.GameID')
                        ->where('GameData.achievements_published', '>', 0)
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
                        if ($maxCount == 0) {
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

        if ($relatedGame != null) {
            return $relatedGame->gameIDAlt;
        }

        return 0;
    }
}