<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketDataTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetTicketDataForTicket(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create([
            'AchievementID' => $achievement->id,
            'reporter_id' => $this->user->id,
            'ticketable_author_id' => $achievement->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['i' => $ticket->ID]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $ticket->ID,
                'AchievementID' => $achievement->id,
                'AchievementTitle' => $achievement->title,
                'AchievementDesc' => $achievement->description,
                'Points' => $achievement->points,
                'BadgeName' => $achievement->image_name,
                'AchievementAuthor' => $achievement->developer->username,
                'AchievementAuthorULID' => $achievement->developer->ulid,
                'GameID' => $game->id,
                'GameTitle' => $game->title,
                'GameIcon' => $game->image_icon_asset_path,
                'ConsoleName' => $system->name,
                'ReportedAt' => $ticket->ReportedAt->__toString(),
                'ReportType' => 2,
                'ReportTypeDescription' => 'Did not trigger',
                'ReportState' => 1,
                'ReportStateDescription' => 'Open',
                'Hardcore' => 1,
                'ReportNotes' => $ticket->ReportNotes,
                'ReportedBy' => $this->user->username,
                'ReportedByULID' => $this->user->ulid,
                'ResolvedAt' => null,
                'ResolvedBy' => null,
                'ResolvedByULID' => null,
                'URL' => config('app.url') . '/ticket/' . $ticket->ID,
            ]);
    }

    public function testGetTicketDataForOpenTickets(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id]);
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create([
            'AchievementID' => $achievement->id,
            'reporter_id' => $this->user->id,
            'ticketable_author_id' => $achievement->user_id,
        ]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game2->id]);
        /** @var Ticket $ticket2 */
        $ticket2 = Ticket::factory()->create([
            'AchievementID' => $achievement2->id,
            'reporter_id' => $user2->id, 'Hardcore' => 0,
            'ReportType' => TicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement2->user_id,
        ]);
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game3->id]);
        Ticket::factory()->create([
            'AchievementID' => $achievement3->id,
            'reporter_id' => $user2->id, 'ReportState' => TicketState::Resolved,
            'resolver_id' => $user3->id, 'ResolvedAt' => Carbon::now(),
            'ticketable_author_id' => $achievement3->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData'))
            ->assertSuccessful()
            ->assertJson([
                'RecentTickets' => [ // tickets returned newest id first (open only)
                    [
                        'ID' => $ticket2->ID,
                        'AchievementID' => $achievement2->id,
                        'AchievementTitle' => $achievement2->title,
                        'AchievementDesc' => $achievement2->description,
                        'Points' => $achievement2->points,
                        'BadgeName' => $achievement2->image_name,
                        'AchievementAuthor' => $achievement2->developer->username,
                        'AchievementAuthorULID' => $achievement2->developer->ulid,
                        'GameID' => $game2->id,
                        'GameTitle' => $game2->title,
                        'GameIcon' => $game2->image_icon_asset_path,
                        'ConsoleName' => $system->name,
                        'ReportedAt' => $ticket2->ReportedAt->__toString(),
                        'ReportType' => 1,
                        'ReportTypeDescription' => 'Triggered at the wrong time',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 0,
                        'ReportNotes' => $ticket2->ReportNotes,
                        'ReportedBy' => $user2->username,
                        'ReportedByULID' => $user2->ulid,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                        'ResolvedByULID' => null,
                    ],
                    [
                        'ID' => $ticket->ID,
                        'AchievementID' => $achievement->id,
                        'AchievementTitle' => $achievement->title,
                        'AchievementDesc' => $achievement->description,
                        'Points' => $achievement->points,
                        'BadgeName' => $achievement->image_name,
                        'AchievementAuthor' => $achievement->developer->username,
                        'AchievementAuthorULID' => $achievement->developer->ulid,
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'GameIcon' => $game->image_icon_asset_path,
                        'ConsoleName' => $system->name,
                        'ReportedAt' => $ticket->ReportedAt->__toString(),
                        'ReportType' => 2,
                        'ReportTypeDescription' => 'Did not trigger',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 1,
                        'ReportNotes' => $ticket->ReportNotes,
                        'ReportedBy' => $this->user->username,
                        'ReportedByULID' => $this->user->ulid,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                        'ResolvedByULID' => null,
                    ],
                ],
                'OpenTickets' => 2,
                'URL' => config('app.url') . '/tickets',
            ]);
    }

    public function testGetTicketDataForMostReportedGames(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        $achievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $game->id]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        $achievements2 = Achievement::factory()->promoted()->count(6)->create(['game_id' => $game2->id]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['system_id' => $system->id]);
        $achievements3 = Achievement::factory()->promoted()->count(6)->create(['game_id' => $game3->id]);

        for ($i = 0; $i < 1; $i++) {
            Ticket::factory()->create([
                'AchievementID' => $achievements->get($i)->id,
                'reporter_id' => $this->user->id,
                'ticketable_author_id' => $achievements->get($i)->user_id,
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            Ticket::factory()->create([
                'AchievementID' => $achievements2->get($i)->id,
                'reporter_id' => $this->user->id,
                'ticketable_author_id' => $achievements2->get($i)->user_id,
            ]);
        }
        for ($i = 0; $i < 3; $i++) {
            Ticket::factory()->create([
                'AchievementID' => $achievements3->get($i)->id,
                'reporter_id' => $this->user->id,
                'ticketable_author_id' => $achievements3->get($i)->user_id,
            ]);
        }

        $this->get($this->apiUrl('GetTicketData', ['f' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'MostReportedGames' => [
                    [
                        'GameID' => $game2->id,
                        'GameTitle' => $game2->title,
                        'GameIcon' => $game2->image_icon_asset_path,
                        'Console' => $system->name,
                        'OpenTickets' => 5,
                    ],
                    [
                        'GameID' => $game3->id,
                        'GameTitle' => $game3->title,
                        'GameIcon' => $game3->image_icon_asset_path,
                        'Console' => $system->name,
                        'OpenTickets' => 3,
                    ],
                    [
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'GameIcon' => $game->image_icon_asset_path,
                        'Console' => $system->name,
                        'OpenTickets' => 1,
                    ],
                ],
                'URL' => config('app.url') . '/manage/most-reported-games',
            ]);
    }

    public function testGetTicketDataForUserByName(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $this->user->id]);
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create([
            'AchievementID' => $achievement->id,
            'reporter_id' => $this->user->id,
            'ticketable_author_id' => $achievement->user_id,
        ]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game2->id, 'user_id' => $this->user->id]);
        /** @var Ticket $ticket2 */
        $ticket2 = Ticket::factory()->create([
            'AchievementID' => $achievement2->id,
            'reporter_id' => $user2->id,
            'Hardcore' => 0,
            'ReportType' => TicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement2->user_id,
        ]);
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game3->id, 'user_id' => $this->user->id]);
        Ticket::factory()->create([
            'AchievementID' => $achievement3->id,
            'reporter_id' => $user2->id,
            'ReportState' => TicketState::Resolved,
            'resolver_id' => $user3->id,
            'ResolvedAt' => Carbon::now(),
            'ticketable_author_id' => $achievement3->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['u' => $this->user->username])) // !!
            ->assertSuccessful()
            ->assertJson([
                'User' => $this->user->username,
                'ULID' => $this->user->ulid,
                'Open' => 2,
                'Closed' => 0,
                'Resolved' => 1,
                'Total' => 3,
                'URL' => config('app.url') . '/user/' . $this->user->username . '/tickets',
            ]);
    }

    public function testGetTicketDataForUserByUlid(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $this->user->id]);
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create([
            'AchievementID' => $achievement->id,
            'reporter_id' => $this->user->id,
            'ticketable_author_id' => $achievement->user_id,
        ]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->promoted()->create(['game_id' => $game2->id, 'user_id' => $this->user->id]);
        /** @var Ticket $ticket2 */
        $ticket2 = Ticket::factory()->create([
            'AchievementID' => $achievement2->id,
            'reporter_id' => $user2->id,
            'Hardcore' => 0,
            'ReportType' => TicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement2->user_id,
        ]);
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->promoted()->create(['game_id' => $game3->id, 'user_id' => $this->user->id]);
        Ticket::factory()->create([
            'AchievementID' => $achievement3->id,
            'reporter_id' => $user2->id,
            'ReportState' => TicketState::Resolved,
            'resolver_id' => $user3->id,
            'ResolvedAt' => Carbon::now(),
            'ticketable_author_id' => $achievement3->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['u' => $this->user->ulid])) // !!
            ->assertSuccessful()
            ->assertJson([
                'User' => $this->user->username,
                'ULID' => $this->user->ulid,
                'Open' => 2,
                'Closed' => 0,
                'Resolved' => 1,
                'Total' => 3,
                'URL' => config('app.url') . '/user/' . $this->user->username . '/tickets',
            ]);
    }

    public function testGetTicketDataForGame(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var User $author */
        $author = User::factory()->create();

        $achievements = Achievement::factory()->promoted()->count(6)->create(['game_id' => $game->id, 'user_id' => $author->id]);

        /** @var Achievement $achievement1 */
        $achievement1 = $achievements->get(0);
        /** @var Achievement $achievement2 */
        $achievement2 = $achievements->get(3);

        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create([
            'AchievementID' => $achievement1->id,
            'reporter_id' => $this->user->id,
            'ticketable_author_id' => $achievement1->user_id,
        ]);
        /** @var Ticket $ticket2 */
        $ticket2 = Ticket::factory()->create([
            'AchievementID' => $achievement2->id,
            'reporter_id' => $this->user->id,
            'Hardcore' => 0,
            'ReportType' => TicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement2->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['g' => $game->id, 'd' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'GameID' => $game->id,
                'GameTitle' => $game->title,
                'OpenTickets' => 2,
                'Tickets' => [ // tickets returned newest id first (open only)
                    [
                        'ID' => $ticket2->ID,
                        'AchievementID' => $achievement2->id,
                        'AchievementTitle' => $achievement2->title,
                        'AchievementDesc' => $achievement2->description,
                        'Points' => $achievement2->points,
                        'BadgeName' => $achievement2->image_name,
                        'AchievementAuthor' => $achievement2->developer->username,
                        'AchievementAuthorULID' => $achievement2->developer->ulid,
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'GameIcon' => $game->image_icon_asset_path,
                        'ConsoleName' => $system->name,
                        'ReportedAt' => $ticket2->ReportedAt->__toString(),
                        'ReportType' => 1,
                        'ReportTypeDescription' => 'Triggered at the wrong time',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 0,
                        'ReportNotes' => $ticket2->ReportNotes,
                        'ReportedBy' => $this->user->username,
                        'ReportedByULID' => $this->user->ulid,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                        'ResolvedByULID' => null,
                    ],
                    [
                        'ID' => $ticket->ID,
                        'AchievementID' => $achievement1->id,
                        'AchievementTitle' => $achievement1->title,
                        'AchievementDesc' => $achievement1->description,
                        'Points' => $achievement1->points,
                        'BadgeName' => $achievement1->image_name,
                        'AchievementAuthor' => $achievement1->developer->username,
                        'AchievementAuthorULID' => $achievement1->developer->ulid,
                        'GameID' => $game->id,
                        'GameTitle' => $game->title,
                        'GameIcon' => $game->image_icon_asset_path,
                        'ConsoleName' => $system->name,
                        'ReportedAt' => $ticket->ReportedAt->__toString(),
                        'ReportType' => 2,
                        'ReportTypeDescription' => 'Did not trigger',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 1,
                        'ReportNotes' => $ticket->ReportNotes,
                        'ReportedBy' => $this->user->username,
                        'ReportedByULID' => $this->user->ulid,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                        'ResolvedByULID' => null,
                    ],
                ],
                'URL' => config('app.url') . '/game/' . $game->id . '/tickets',
            ]);
    }

    public function testGetTicketDataForAchievement(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['system_id' => $system->id]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->promoted()->create(['game_id' => $game->id, 'user_id' => $this->user->id]);
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create([
            'AchievementID' => $achievement->id,
            'reporter_id' => $this->user->id,
            'ticketable_author_id' => $achievement->user_id,
        ]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Ticket $ticket2 */
        $ticket2 = Ticket::factory()->create([
            'AchievementID' => $achievement->id,
            'reporter_id' => $user2->id,
            'Hardcore' => 0,
            'ReportType' => TicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['a' => $achievement->id]))
            ->assertSuccessful()
            ->assertJson([
                'AchievementID' => $achievement->id,
                'AchievementTitle' => $achievement->title,
                'AchievementDescription' => $achievement->description,
                'OpenTickets' => 2,
                'URL' => config('app.url') . '/achievement/' . $achievement->id . '/tickets',
            ]);
    }
}
