<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Actions\GetUserDeviceKindAction;
use App\Community\Data\GameSetRequestsPagePropsData;
use App\Community\Enums\UserGameListType;
use App\Data\UserData;
use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameSet;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
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
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\GameListSetTypeFilterValue;
use App\Platform\Enums\GameListSortField;
use App\Platform\Enums\GameListType;
use App\Platform\Enums\GamePageListSort;
use App\Platform\Enums\GamePageListView;
use App\Platform\Enums\GameSetType;
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
        BuildGameShowPagePropsAction $buildGameShowPagePropsAction,
    ): InertiaResponse|RedirectResponse {
        $this->authorize('view', $game);

        /** @var ?User $user */
        $user = $request->user();

        // Redirect hubs to the dedicated hub page.
        if ($game->system_id === System::Hubs) {
            $gameSet = GameSet::whereType(GameSetType::Hub)
                ->whereGameId($game->id)
                ->first();

            if ($gameSet) {
                return redirect()->route('hub.show', ['gameSet' => $gameSet]);
            }

            abort(404);
        }

        // Redirect events to the dedicated event page.
        if ($game->system_id === System::Events && $game->event) {
            return redirect()->route('event.show', ['event' => $game->event]);
        }

        // Redirect the legacy ?f=5 parameter to ?unpublished=true.
        if ($request->query('f') === '5') {
            $queryParams = $request->query();
            unset($queryParams['f']);
            $queryParams['unpublished'] = 'true';

            return redirect()->route('game.show', array_merge(['game' => $game], $queryParams));
        }

        // Get the target achievement set ID from query params.
        $targetAchievementSetId = $request->query('set') ? (int) $request->query('set') : null;

        // Check if this is a subset game that should redirect to its backing game.
        // eg: "/game/24186" -> "/game/668?set=8659"
        if (!$targetAchievementSetId) {
            $redirectResponse = $this->checkSubsetGameRedirect($request, $game);
            if ($redirectResponse) {
                return $redirectResponse;
            }
        }

        // Get whether to show published or unpublished achievements from query params.
        $targetAchievementFlag =
            $request->query('unpublished') === 'true'
                ? AchievementFlag::Unofficial
                : AchievementFlag::OfficialCore;

        // Load the target achievement set if requested.
        $targetAchievementSet = null;
        if ($targetAchievementSetId !== null) {
            $targetAchievementSet = $game->gameAchievementSets()
                ->where('achievement_set_id', $targetAchievementSetId)
                ->with('achievementSet')
                ->first();

            if (!$targetAchievementSet) {
                // Invalid set ID for this game. Redirect to the game without the set parameter.
                return redirect()->route('game.show', ['game' => $game]);
            }
        }

        // Get the initial view from query params.
        $initialView = GamePageListView::tryFrom($request->query('view', '')) ?? GamePageListView::Achievements;

        // Get the initial sort from query params.
        $initialSort = $request->query('sort') ? GamePageListSort::tryFrom($request->query('sort')) : null;

        $game = $loadGameWithRelationsAction->execute($game, $targetAchievementFlag, $targetAchievementSet);
        $props = $buildGameShowPagePropsAction->execute(
            $game,
            $user,
            $targetAchievementFlag,
            $targetAchievementSet,
            $initialView,
            $initialSort
        );

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

    /**
     * Shows the set requestors for a given game
     */
    public function setRequests(Request $request, Game $game): InertiaResponse
    {
        $allRequestors = UserGameListEntry::where('GameID', $game->id)
            ->where('type', UserGameListType::AchievementSetRequest)
            ->join('UserAccounts', 'SetRequest.user_id', '=', 'UserAccounts.ID')
            ->orderBy('UserAccounts.display_name')
            ->with('user')
            ->get();

        // Split the requestors into initial and deferred groups.
        $initialRequestors = $allRequestors->take(100);
        $deferredRequestors = $allRequestors->skip(100);

        // Map the results to minimal data objects with only the needed fields for the UI.
        $initialRequestorsData = $initialRequestors
            ->map(fn ($badge) => UserData::fromUser($badge->user)->include('displayName', 'avatarUrl', 'id'))
            ->values();

        $deferredRequestorsData = $deferredRequestors
            ->map(fn ($badge) => UserData::fromUser($badge->user)->include('displayName', 'avatarUrl', 'id'))
            ->values();

        $props = new GameSetRequestsPagePropsData(
            game: GameData::fromGame($game)->include('badgeUrl', 'system'),
            initialRequestors: $initialRequestorsData,
            deferredRequestors: Inertia::defer(fn () => $deferredRequestorsData),
            totalCount: $allRequestors->count(),
        );

        return Inertia::render('game/[game]/requests', $props);
    }

    /**
     * Check if a game is a subset that should redirect to its backing game.
     */
    private function checkSubsetGameRedirect(Request $request, Game $game): ?RedirectResponse
    {
        // Find this game's core achievement set.
        $coreSet = $game->gameAchievementSets()
            ->where('type', AchievementSetType::Core)
            ->select('achievement_set_id')
            ->first();

        if (!$coreSet) {
            return null;
        }

        // Check if this achievement set exists in another game as non-core.
        $backingGameSet = GameAchievementSet::where('achievement_set_id', $coreSet->achievement_set_id)
            ->whereNotIn('type', [AchievementSetType::Core])
            ->select('game_id')
            ->first();

        if (!$backingGameSet || $backingGameSet->game_id === $game->id) {
            return null;
        }

        // Redirect to the backing game with the set parameter.
        $queryParams = $request->query();
        $queryParams['set'] = $coreSet->achievement_set_id;

        return redirect()->route('game.show', array_merge(['game' => $backingGameSet->game_id], $queryParams));
    }
}
