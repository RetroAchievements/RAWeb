<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Data\UserData;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\TicketData;
use App\Platform\Enums\GameSetType;
use Illuminate\Support\Collection;

class FetchDynamicShortcodeContentAction
{
    public function execute(
        array $usernames = [],
        array $ticketIds = [],
        array $achievementIds = [],
        array $gameIds = [],
        array $hubIds = [],
    ): array {
        $results = collect([
            'users' => $this->fetchUsers($usernames),
            'tickets' => $this->fetchTickets($ticketIds),
            'achievements' => $this->fetchAchievements($achievementIds),
            'games' => $this->fetchGames($gameIds),
            'hubs' => $this->fetchHubs($hubIds),
        ]);

        return $results->toArray();
    }

    /**
     * @return Collection<int, UserData>
     */
    private function fetchUsers(array $usernames): Collection
    {
        if (empty($usernames)) {
            return collect();
        }

        $users = User::query()
            ->where(function ($query) use ($usernames) {
                $query->whereIn('User', $usernames)
                    ->orWhereIn('display_name', $usernames);
            })
            ->get();

        return $users->map(fn (User $user) => UserData::fromUser($user));
    }

    /**
     * @return Collection<int, TicketData>
     */
    private function fetchTickets(array $ticketIds): Collection
    {
        if (empty($ticketIds)) {
            return collect();
        }

        return Ticket::with('achievement')
            ->whereIn('ID', $ticketIds)
            ->get()
            ->map(fn (Ticket $ticket) => TicketData::fromTicket($ticket)->include('state', 'ticketable'));
    }

    /**
     * @return Collection<int, AchievementData>
     */
    private function fetchAchievements(array $achievementIds): Collection
    {
        if (empty($achievementIds)) {
            return collect();
        }

        return Achievement::whereIn('ID', $achievementIds)
            ->get()
            ->map(fn (Achievement $achievement) => AchievementData::fromAchievement($achievement)->include(
                'badgeUnlockedUrl',
                'points'
            ));
    }

    /**
     * @return Collection<int, GameData>
     */
    private function fetchGames(array $gameIds): Collection
    {
        if (empty($gameIds)) {
            return collect();
        }

        return Game::with('system')
            ->whereIn('ID', $gameIds)
            ->get()
            ->map(fn (Game $game) => GameData::fromGame($game)->include('badgeUrl', 'system.name'));
    }

    /**
     * @return Collection<int, GameSetData>
     */
    private function fetchHubs(array $hubIds): Collection
    {
        if (empty($hubIds)) {
            return collect();
        }

        return GameSet::whereIn('id', $hubIds)
            ->where('type', GameSetType::Hub)
            ->get()
            ->map(fn (GameSet $gameSet) => GameSetData::fromGameSetWithCounts($gameSet)->include('gameId'));
    }
}
