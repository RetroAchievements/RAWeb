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

class BuildActivePlayersAction
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

        // Put game IDs in a dictionary for O(1) lookups.
        $gameIdSet = [];
        foreach ($gameIds as $id) {
            $gameIdSet[$id] = true;
        }

        return array_values(
            array_filter(
                $players,
                fn ($player) => isset($gameIdSet[$player['game_id']])
            )
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

        if ($search === '') {
            return $players;
        }

        // We also support logical OR searches, for example:
        // "developing|inspecting".
        $searchTerms = array_filter(
            array_map(
                fn (string $term) => Str::lower(trim($term)),
                explode('|', $search)
            ),
            fn ($term) => $term !== ''
        );

        if (empty($searchTerms)) {
            return $players;
        }

        // This lookup function checks all fields at once.
        $checkPlayer = function (array $player) use ($searchTerms): bool {
            // Combine all searchable fields into one string and lowercase it.
            $searchableText = Str::lower(
                $player['username'] . ' ' .
                $player['display_name'] . ' ' .
                $player['rich_presence'] . ' ' .
                $player['game_title'] . ' '
            );

            foreach ($searchTerms as $term) {
                if (str_contains($searchableText, $term)) {
                    return true;
                }
            }

            return false;
        };

        return array_values(array_filter($players, $checkPlayer));
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

            return ActivePlayerData::fromHydratedCachedValue($user, $game);
        })->filter();
    }
}
