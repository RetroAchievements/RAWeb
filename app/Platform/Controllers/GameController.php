<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Actions\GetUserDeviceKindAction;
use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\BuildGameInterestedDevelopersDataAction;
use App\Platform\Actions\BuildGameListAction;
use App\Platform\Actions\BuildGameShowPagePropsAction;
use App\Platform\Actions\GetRandomGameAction;
use App\Platform\Actions\LoadGameWithRelationsAction;
use App\Platform\Data\DeveloperInterestPagePropsData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameListPagePropsData;
use App\Platform\Data\GameSuggestPagePropsData;
use App\Platform\Data\SystemData;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\GameListSetTypeFilterValue;
use App\Platform\Enums\GameListSortField;
use App\Platform\Enums\GameListType;
use App\Platform\Requests\GameListRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameController extends Controller
{
    public function index(GameListRequest $request): InertiaResponse
    {
        /** @var ?User $user */
        $user = $request->user();

        $this->authorize('viewAny', [Game::class, $user]);

        $persistenceCookieName = 'datatable_view_preference_all_games';
        $request->setPersistenceCookieName($persistenceCookieName);

        $isMobile = (new GetUserDeviceKindAction())->execute() === 'mobile';

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::AllGames,
            user: $user,
            filters: $request->getFilters(defaultSubsetFilter: GameListSetTypeFilterValue::OnlyGames),
            sort: $request->getSort(
                defaultSortField: GameListSortField::PlayersTotal,
                isDefaultSortAsc: false,
            ),
            perPage: $isMobile ? 100 : $request->getPageSize(),

            /**
             * Ignore page params on mobile.
             * They're _always_ desktop-generated. Desktop uses smaller
             * page sizes, so respecting these params is highly undesirable.
             */
            page: $isMobile ? 1 : $request->getPage(),
        );

        $filterableSystemOptions = System::active()
            ->gameSystems()
            ->get()
            ->map(fn ($system) => SystemData::fromSystem($system)->include('nameShort'))
            ->values()
            ->all();

        $can = UserPermissionsData::fromUser($user)->include('develop');

        $props = new GameListPagePropsData(
            paginatedGameListEntries: $paginatedData,
            filterableSystemOptions: $filterableSystemOptions,
            can: $can,
            persistenceCookieName: $persistenceCookieName,
            persistedViewPreferences: $request->getCookiePreferences(),
        );

        return Inertia::render('games', $props);
    }

    public function show(
        Request $request,
        Game $game,
        LoadGameWithRelationsAction $loadGameWithRelationsAction,
        BuildGameShowPagePropsAction $buildGameShowPagePropsAction
    ): InertiaResponse|RedirectResponse {
        $this->authorize('view', $game);

        /** @var ?User $user */
        $user = $request->user();

        // Get the target achievement set ID from query parameter.
        $targetAchievementSetId = $request->query('set') ? (int) $request->query('set') : null;

        // If a target achievement set is requested, validate it actually belongs to this game.
        if ($targetAchievementSetId !== null) {
            $validSetExists = $game->gameAchievementSets()
                ->where('achievement_set_id', $targetAchievementSetId)
                ->exists();

            if (!$validSetExists) {
                // Invalid set ID for this game. Redirect to the game without the set parameter.
                return redirect()->route('game.show', ['game' => $game]);
            }
        }

        $game = $loadGameWithRelationsAction->execute($game, AchievementFlag::OfficialCore, $targetAchievementSetId);
        $props = $buildGameShowPagePropsAction->execute($game, $user, $targetAchievementSetId);

        return Inertia::render('game/[game]', $props);
    }

    public function destroy(Game $game): void
    {
        $this->authorize('delete', $game);
    }

    public function devInterest(Game $game): InertiaResponse
    {
        $this->authorize('viewDeveloperInterest', $game);

        $props = new DeveloperInterestPagePropsData(
            game: GameData::fromGame($game)->include('badgeUrl', 'system'),
            developers: (new BuildGameInterestedDevelopersDataAction())->execute($game)
        );

        return Inertia::render('game/[game]/dev-interest', $props);
    }

    public function random(GameListRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Game::class);

        $randomGame = (new GetRandomGameAction())->execute(
            GameListType::AllGames,
            filters: $request->getFilters(),
        );

        if (!$randomGame) {
            return redirect()->back()->with('error', 'No games with achievements found.');
        }

        return redirect()->route('game.show', ['game' => $randomGame]);
    }

    public function suggestPersonalized(GameListRequest $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if(!$user, 404);

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::UserSpecificSuggestions,
            user: $user,
            perPage: 10,
            page: 1,
        );

        $props = new GameSuggestPagePropsData(
            paginatedGameListEntries: $paginatedData,
        );

        return Inertia::render('games/suggestions', $props);
    }

    public function suggestSimilar(GameListRequest $request, Game $game): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_if(!$user, 404);

        $paginatedData = (new BuildGameListAction())->execute(
            GameListType::GameSpecificSuggestions,
            user: $user,
            targetId: $game->id,
            perPage: 10,
            page: 1,
        );

        $props = new GameSuggestPagePropsData(
            paginatedGameListEntries: $paginatedData,
            sourceGame: GameData::from($game)->include('badgeUrl'),
        );

        return Inertia::render('game/[game]/suggestions', $props);
    }
}
