<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\ActivePlayerData;
use App\Data\PaginatedData;
use App\Models\Game;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BuildAllActivePlayersAction
{
    public function execute(
        int $perPage = 300,
        int $page = 1,
        ?array $gameIds = null,
        ?string $search = null,
    ): PaginatedData {
        $activePlayersList = (new LoadThinActivePlayersListAction())->execute();
        $unfilteredTotal = count($activePlayersList);

        // Filter the results.
        $activePlayersList = $this->filterByGameIds($activePlayersList, $gameIds);
        $activePlayersList = $this->filterBySearch($activePlayersList, $search);

        // Paginate the results.
        $offset = ($page - 1) * $perPage;
        $paginatedPlayers = array_slice($activePlayersList, $offset, $perPage);

        $hydratedPlayers = $this->hydrateResults($paginatedPlayers);

        $paginator = new LengthAwarePaginator(
            items: $hydratedPlayers,
            total: count($activePlayersList),
            perPage: $perPage,
            currentPage: $page,
        );

        return PaginatedData::fromLengthAwarePaginator($paginator, unfilteredTotal: $unfilteredTotal);
    }

    /**
     * @param array<array{
     *   user_id: int,
     *   game_id: int,
     *   username: string,
     *   display_name: string,
     *   rich_presence: string,
     *   game_title: string,
     * }> $players
     *
     * @return array<array{
     *   user_id: int,
     *   game_id: int,
     *   username: string,
     *   display_name: string,
     *   rich_presence: string,
     *   game_title: string,
     * }>
     */
    private function filterByGameIds(array $players, ?array $gameIds = null): array
    {
        if (!$gameIds) {
            return $players;
        }

        return array_filter(
            $players,
            fn ($player) => in_array($player['game_id'], $gameIds, strict: true)
        );
    }

    /**
     * @param array<array{
     *   user_id: int,
     *   game_id: int,
     *   username: string,
     *   display_name: string,
     *   rich_presence: string,
     *   game_title: string,
     * }> $players
     *
     * @return array<array{
     *   user_id: int,
     *   game_id: int,
     *   username: string,
     *   display_name: string,
     *   rich_presence: string,
     *   game_title: string,
     * }>
     */
    private function filterBySearch(array $players, ?string $search): array
    {
        if (!$search) {
            return $players;
        }

        $search = Str::lower(trim($search));

        // We should also support logical OR searches, for example:
        // "developing || inspecting".
        $searchTerms = array_map(
            fn (string $term) => Str::lower(trim($term)),
            explode('||', $search)
        );

        // Filter out any empty terms.
        $searchTerms = array_filter($searchTerms);
        if (empty($searchTerms)) {
            return $players;
        }

        return array_filter(
            $players,
            function ($player) use ($searchTerms) {
                // For each search term, check if it exists in any of the fields.
                foreach ($searchTerms as $term) {
                    if (
                        str_contains(Str::lower($player['username']), $term)
                        || str_contains(Str::lower($player['display_name']), $term)
                        || str_contains(Str::lower($player['rich_presence']), $term)
                        || str_contains(Str::lower($player['game_title']), $term)
                    ) {
                        return true;
                    }
                }

                return false;
            }
        );
    }

    /**
     * @param array<array{
     *   user_id: int,
     *   game_id: int,
     *   username: string,
     *   display_name: string,
     *   rich_presence: string,
     *   game_title: string,
     * }> $players
     *
     * @return Collection<int, ActivePlayerData>
     */
    private function hydrateResults(array $players): Collection
    {
        if (empty($players)) {
            return collect();
        }

        // Extract unique IDs.
        $userIds = array_column($players, 'user_id');
        $gameIds = array_column($players, 'game_id');

        // Fetch all users and games in bulk.
        $users = User::whereIn('ID', $userIds)->get()->keyBy('id');
        $games = Game::whereIn('ID', $gameIds)->get()->keyBy('id');

        // Map the results to ActivePlayerData objects.
        return collect($players)->map(function ($player) use ($users, $games) {
            $user = $users->get($player['user_id']);
            $game = $games->get($player['game_id']);

            if (!$user || !$game) {
                return null;
            }

            return ActivePlayerData::fromHydratedTuple($user, $game);
        })->filter();
    }
}
