<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\ShortcodeDynamicEntitiesData;
use App\Data\UserData;
use App\Models\Achievement;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\EventData;
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
        array $eventIds = [],
    ): ShortcodeDynamicEntitiesData {
        return new ShortcodeDynamicEntitiesData(
            users: $this->fetchUsers($usernames)->all(),
            tickets: $this->fetchTickets($ticketIds)->all(),
            achievements: $this->fetchAchievements($achievementIds)->all(),
            games: $this->fetchGames($gameIds)->all(),
            hubs: $this->fetchHubs($hubIds)->all(),
            events: $this->fetchEvents($eventIds)->all(),
        );
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
            ->withTrashed()
            ->where(function ($query) use ($usernames) {
                $query->whereIn('User', $usernames)
                    ->orWhereIn('display_name', $usernames);
            })
            ->get();

        return $users->map(fn (User $user) => UserData::fromUser($user)->include('deletedAt'));
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

    /**
     * @return Collection<int, EventData>
     */
    private function fetchEvents(array $eventIds): Collection
    {
        if (empty($eventIds)) {
            return collect();
        }

        return Event::query()
            ->with('legacyGame')
            ->whereIn('id', $eventIds)
            ->get()
            ->map(fn (Event $event) => EventData::fromEvent($event)->include('legacyGame.badgeUrl'));
    }
}
