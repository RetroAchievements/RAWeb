<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\TriggerTicketState;
use App\Community\Enums\TriggerTicketType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\TriggerTicket;
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
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var TriggerTicket $ticket */
        $ticket = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement->id,
            'reporter_id' => $this->user->id,
            'ticketable_author_id' => $achievement->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['i' => $ticket->id]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $ticket->id,
                'AchievementID' => $achievement->ID,
                'AchievementTitle' => $achievement->Title,
                'AchievementDesc' => $achievement->Description,
                'Points' => $achievement->Points,
                'BadgeName' => $achievement->BadgeName,
                'AchievementAuthor' => $achievement->developer->User,
                'AchievementAuthorULID' => $achievement->developer->ulid,
                'GameID' => $game->ID,
                'GameTitle' => $game->Title,
                'GameIcon' => $game->ImageIcon,
                'ConsoleName' => $system->Name,
                'ReportedAt' => $ticket->created_at->__toString(),
                'ReportType' => 2,
                'ReportTypeDescription' => 'Did not trigger',
                'ReportState' => 1,
                'ReportStateDescription' => 'Open',
                'Hardcore' => 1,
                'ReportNotes' => $ticket->body,
                'ReportedBy' => $this->user->User,
                'ReportedByULID' => $this->user->ulid,
                'ResolvedAt' => null,
                'ResolvedBy' => null,
                'ResolvedByULID' => null,
                'URL' => config('app.url') . '/ticket/' . $ticket->id,
            ]);
    }

    public function testGetTicketDataForOpenTickets(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID]);
        /** @var TriggerTicket $ticket */
        $ticket = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement->ID,
            'reporter_id' => $this->user->ID,
            'ticketable_author_id' => $achievement->user_id,
        ]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game2->ID]);
        /** @var TriggerTicket $ticket2 */
        $ticket2 = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement2->ID,
            'reporter_id' => $user2->ID, 'hardcore' => 0,
            'type' => TriggerTicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement2->user_id,
        ]);
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game3->ID]);
        TriggerTicket::factory()->create([
            'ticketable_id' => $achievement3->ID,
            'reporter_id' => $user2->ID, 'state' => TriggerTicketState::Resolved,
            'resolver_id' => $user3->ID, 'resolved_at' => Carbon::now(),
            'ticketable_author_id' => $achievement3->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData'))
            ->assertSuccessful()
            ->assertJson([
                'RecentTickets' => [ // tickets returned newest id first (open only)
                    [
                        'ID' => $ticket2->id,
                        'AchievementID' => $achievement2->ID,
                        'AchievementTitle' => $achievement2->Title,
                        'AchievementDesc' => $achievement2->Description,
                        'Points' => $achievement2->Points,
                        'BadgeName' => $achievement2->BadgeName,
                        'AchievementAuthor' => $achievement2->developer->User,
                        'AchievementAuthorULID' => $achievement2->developer->ulid,
                        'GameID' => $game2->ID,
                        'GameTitle' => $game2->Title,
                        'GameIcon' => $game2->ImageIcon,
                        'ConsoleName' => $system->Name,
                        'ReportedAt' => $ticket2->created_at->__toString(),
                        'ReportType' => 1,
                        'ReportTypeDescription' => 'Triggered at the wrong time',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 0,
                        'ReportNotes' => $ticket2->body,
                        'ReportedBy' => $user2->User,
                        'ReportedByULID' => $user2->ulid,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                        'ResolvedByULID' => null,
                    ],
                    [
                        'ID' => $ticket->id,
                        'AchievementID' => $achievement->ID,
                        'AchievementTitle' => $achievement->Title,
                        'AchievementDesc' => $achievement->Description,
                        'Points' => $achievement->Points,
                        'BadgeName' => $achievement->BadgeName,
                        'AchievementAuthor' => $achievement->developer->User,
                        'AchievementAuthorULID' => $achievement->developer->ulid,
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'GameIcon' => $game->ImageIcon,
                        'ConsoleName' => $system->Name,
                        'ReportedAt' => $ticket->created_at->__toString(),
                        'ReportType' => 2,
                        'ReportTypeDescription' => 'Did not trigger',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 1,
                        'ReportNotes' => $ticket->body,
                        'ReportedBy' => $this->user->User,
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
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $achievements = Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID]);
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        $achievements2 = Achievement::factory()->published()->count(6)->create(['GameID' => $game2->ID]);
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['ConsoleID' => $system->ID]);
        $achievements3 = Achievement::factory()->published()->count(6)->create(['GameID' => $game3->ID]);

        for ($i = 0; $i < 1; $i++) {
            TriggerTicket::factory()->create([
                'ticketable_id' => $achievements->get($i)->ID,
                'reporter_id' => $this->user->ID,
                'ticketable_author_id' => $achievements->get($i)->user_id,
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            TriggerTicket::factory()->create([
                'ticketable_id' => $achievements2->get($i)->ID,
                'reporter_id' => $this->user->ID,
                'ticketable_author_id' => $achievements2->get($i)->user_id,
            ]);
        }
        for ($i = 0; $i < 3; $i++) {
            TriggerTicket::factory()->create([
                'ticketable_id' => $achievements3->get($i)->ID,
                'reporter_id' => $this->user->ID,
                'ticketable_author_id' => $achievements3->get($i)->user_id,
            ]);
        }

        $this->get($this->apiUrl('GetTicketData', ['f' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'MostReportedGames' => [
                    [
                        'GameID' => $game2->ID,
                        'GameTitle' => $game2->Title,
                        'GameIcon' => $game2->ImageIcon,
                        'Console' => $system->Name,
                        'OpenTickets' => 5,
                    ],
                    [
                        'GameID' => $game3->ID,
                        'GameTitle' => $game3->Title,
                        'GameIcon' => $game3->ImageIcon,
                        'Console' => $system->Name,
                        'OpenTickets' => 3,
                    ],
                    [
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'GameIcon' => $game->ImageIcon,
                        'Console' => $system->Name,
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
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $this->user->id]);
        /** @var TriggerTicket $ticket */
        $ticket = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement->ID,
            'reporter_id' => $this->user->ID,
            'ticketable_author_id' => $achievement->user_id,
        ]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game2->ID, 'user_id' => $this->user->id]);
        /** @var TriggerTicket $ticket2 */
        $ticket2 = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement2->ID,
            'reporter_id' => $user2->ID,
            'hardcore' => 0,
            'type' => TriggerTicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement2->user_id,
        ]);
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game3->ID, 'user_id' => $this->user->id]);
        TriggerTicket::factory()->create([
            'ticketable_id' => $achievement3->ID,
            'reporter_id' => $user2->ID,
            'state' => TriggerTicketState::Resolved,
            'resolver_id' => $user3->ID,
            'resolved_at' => Carbon::now(),
            'ticketable_author_id' => $achievement3->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['u' => $this->user->User])) // !!
            ->assertSuccessful()
            ->assertJson([
                'User' => $this->user->User,
                'ULID' => $this->user->ulid,
                'Open' => 2,
                'Closed' => 0,
                'Resolved' => 1,
                'Total' => 3,
                'URL' => config('app.url') . '/user/' . $this->user->User . '/tickets',
            ]);
    }

    public function testGetTicketDataForUserByUlid(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $this->user->id]);
        /** @var TriggerTicket $ticket */
        $ticket = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement->ID,
            'reporter_id' => $this->user->ID,
            'ticketable_author_id' => $achievement->user_id,
        ]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game2->ID, 'user_id' => $this->user->id]);
        /** @var TriggerTicket $ticket2 */
        $ticket2 = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement2->ID,
            'reporter_id' => $user2->ID,
            'hardcore' => 0,
            'type' => TriggerTicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement2->user_id,
        ]);
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game3->ID, 'user_id' => $this->user->id]);
        TriggerTicket::factory()->create([
            'ticketable_id' => $achievement3->ID,
            'reporter_id' => $user2->ID,
            'state' => TriggerTicketState::Resolved,
            'resolver_id' => $user3->ID,
            'resolved_at' => Carbon::now(),
            'ticketable_author_id' => $achievement3->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['u' => $this->user->ulid])) // !!
            ->assertSuccessful()
            ->assertJson([
                'User' => $this->user->User,
                'ULID' => $this->user->ulid,
                'Open' => 2,
                'Closed' => 0,
                'Resolved' => 1,
                'Total' => 3,
                'URL' => config('app.url') . '/user/' . $this->user->User . '/tickets',
            ]);
    }

    public function testGetTicketDataForGame(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var User $author */
        $author = User::factory()->create();

        $achievements = Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID, 'user_id' => $author->id]);

        /** @var Achievement $achievement1 */
        $achievement1 = $achievements->get(0);
        /** @var Achievement $achievement2 */
        $achievement2 = $achievements->get(3);

        /** @var TriggerTicket $ticket */
        $ticket = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement1->ID,
            'reporter_id' => $this->user->ID,
            'ticketable_author_id' => $achievement1->user_id,
        ]);
        /** @var TriggerTicket $ticket2 */
        $ticket2 = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement2->ID,
            'reporter_id' => $this->user->ID,
            'hardcore' => 0,
            'type' => TriggerTicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement2->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['g' => $game->ID, 'd' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'GameID' => $game->ID,
                'GameTitle' => $game->Title,
                'OpenTickets' => 2,
                'Tickets' => [ // tickets returned newest id first (open only)
                    [
                        'ID' => $ticket2->id,
                        'AchievementID' => $achievement2->ID,
                        'AchievementTitle' => $achievement2->Title,
                        'AchievementDesc' => $achievement2->Description,
                        'Points' => $achievement2->Points,
                        'BadgeName' => $achievement2->BadgeName,
                        'AchievementAuthor' => $achievement2->developer->User,
                        'AchievementAuthorULID' => $achievement2->developer->ulid,
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'GameIcon' => $game->ImageIcon,
                        'ConsoleName' => $system->Name,
                        'ReportedAt' => $ticket2->created_at->__toString(),
                        'ReportType' => 1,
                        'ReportTypeDescription' => 'Triggered at the wrong time',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 0,
                        'ReportNotes' => $ticket2->body,
                        'ReportedBy' => $this->user->User,
                        'ReportedByULID' => $this->user->ulid,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                        'ResolvedByULID' => null,
                    ],
                    [
                        'ID' => $ticket->id,
                        'AchievementID' => $achievement1->ID,
                        'AchievementTitle' => $achievement1->Title,
                        'AchievementDesc' => $achievement1->Description,
                        'Points' => $achievement1->Points,
                        'BadgeName' => $achievement1->BadgeName,
                        'AchievementAuthor' => $achievement1->developer->User,
                        'AchievementAuthorULID' => $achievement1->developer->ulid,
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'GameIcon' => $game->ImageIcon,
                        'ConsoleName' => $system->Name,
                        'ReportedAt' => $ticket->created_at->__toString(),
                        'ReportType' => 2,
                        'ReportTypeDescription' => 'Did not trigger',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 1,
                        'ReportNotes' => $ticket->body,
                        'ReportedBy' => $this->user->User,
                        'ReportedByULID' => $this->user->ulid,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                        'ResolvedByULID' => null,
                    ],
                ],
                'URL' => config('app.url') . '/game/' . $game->ID . '/tickets',
            ]);
    }

    public function testGetTicketDataForAchievement(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID, 'user_id' => $this->user->id]);
        /** @var TriggerTicket $ticket */
        $ticket = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement->ID,
            'reporter_id' => $this->user->ID,
            'ticketable_author_id' => $achievement->user_id,
        ]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var TriggerTicket $ticket2 */
        $ticket2 = TriggerTicket::factory()->create([
            'ticketable_id' => $achievement->ID,
            'reporter_id' => $user2->ID,
            'hardcore' => 0,
            'type' => TriggerTicketType::TriggeredAtWrongTime,
            'ticketable_author_id' => $achievement->user_id,
        ]);

        $this->get($this->apiUrl('GetTicketData', ['a' => $achievement->ID]))
            ->assertSuccessful()
            ->assertJson([
                'AchievementID' => $achievement->ID,
                'AchievementTitle' => $achievement->Title,
                'AchievementDescription' => $achievement->Description,
                'OpenTickets' => 2,
                'URL' => config('app.url') . '/achievement/' . $achievement->ID . '/tickets',
            ]);
    }
}
