<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Community\Enums\ArticleType;
use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\GameRelease;
use App\Models\Role;
use App\Models\System;
use App\Platform\Actions\UpsertGameCoreAchievementSetFromLegacyFlagsAction;
use Illuminate\Http\Request;

class SubmitGameTitleAction extends BaseAuthenticatedApiAction
{
    protected string $hash;
    protected ?string $hashDescription;
    protected string $gameTitle;
    protected int $systemId;
    protected ?int $gameId;

    public function execute(string $hash, ?string $hashDescription, string $gameTitle, int $systemId, ?int $gameId = null): array
    {
        if (!$this->authenticate()) {
            return $this->accessDenied();
        }

        $this->hash = $hash;
        $this->gameTitle = $gameTitle;
        $this->hashDescription = $hashDescription;
        $this->systemId = $systemId;
        $this->gameId = $gameId;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['m', 'i', 'c'])) {
            return $this->missingParameters();
        }

        $this->hash = request()->input('m') ?? '';
        $this->hashDescription = request()->input('d');
        $this->gameTitle = request()->input('i') ?? '';
        $this->systemId = request()->integer('c', 0);
        $this->gameId = request()->integer('g');

        return null;
    }

    protected function process(): array
    {
        if (!$this->user->can('create', Game::class)) {
            return $this->accessDenied('You must be a developer to perform this action.');
        }

        if (mb_strlen($this->hash) != 32) {
            return $this->invalidParameter('Hash must be 32 characters long.');
        }

        if (mb_strlen($this->gameTitle) < 2) {
            return $this->invalidParameter('Title must be at least two characters long.');
        }

        if ($this->gameId) {
            // trying to add hash to existing game
            $game = Game::find($this->gameId);
            if (!$game) {
                return $this->gameNotFound();
            }
            if (!$game->system->active && !$this->user->hasRole(Role::GAME_HASH_MANAGER)) {
                return $this->accessDenied('You do not have permission to add hashes to games for an inactive system.');
            }
        } else {
            $system = System::find($this->systemId);
            if (!$system) {
                return $this->invalidParameter('Unknown system ID.');
            }
            if (!$system->active && !$this->user->hasRole(Role::GAME_HASH_MANAGER)) {
                return $this->accessDenied('You do not have permission to add games to an inactive system.');
            }

            // title must be unique.
            // If the title already exists, just try to add the hash to the existing game
            $release = GameRelease::query()
                ->where('title', $this->gameTitle)
                ->with('game')
                ->whereHas('game.system', function ($query) {
                    $query->where('ID', $this->systemId);
                })
                ->first();
            if ($release) {
                $game = $release->game;
            } else {
                // no title match, it's a new game
                $game = new Game(['Title' => $this->gameTitle, 'ConsoleID' => $this->systemId]);
                // these properties are not fillable, so have to be set manually
                $game->players_total = 0;
                $game->players_hardcore = 0;
                $game->points_total = 0;
                $game->achievements_published = 0;
                $game->achievements_unpublished = 0;
                $game->save();

                // Create the initial canonical title in game_releases.
                $game->releases()->create([
                    'title' => $this->gameTitle,
                    'is_canonical_game_title' => true,
                ]);

                // create an empty GameAchievementSet and AchievementSet
                (new UpsertGameCoreAchievementSetFromLegacyFlagsAction())->execute($game);
            }
        }

        // associate the new hash if it doesn't already exist
        $gameHash = GameHash::firstOrCreate([
            'game_id' => $game->id,
            'md5' => $this->hash,
        ], [
            'name' => !empty($this->hashDescription) ? $this->hashDescription : null,
            'compatibility' => GameHashCompatibility::Compatible,
            'user_id' => $this->user->id,
        ]);

        if ($gameHash->wasRecentlyCreated) {
            // log hash linked
            $message = "$this->hash linked by {$this->user->display_name}.";
            if (!empty($this->hashDescription)) {
                $message .= " Description: \"$this->hashDescription\"";
            }
            addArticleComment('Server', ArticleType::GameHash, $game->id, $message);
        }

        return [
            'Success' => true,
            'GameID' => $game->id, // simplify access for internal use
            'Response' => ['GameID' => $game->id], // clients expect this value to be nested
        ];
    }
}
