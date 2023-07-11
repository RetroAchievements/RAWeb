<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Community\Models\Ticket;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\System;
use App\Site\Models\User;
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
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create(['AchievementID' => $achievement->ID, 'ReportedByUserID' => $this->user->ID]);

        $this->get($this->apiUrl('GetTicketData', ['i' => $ticket->ID]))
            ->assertSuccessful()
            ->assertJson([
                'ID' => $ticket->ID,
                'AchievementID' => $achievement->ID,
                'AchievementTitle' => $achievement->Title,
                'AchievementDesc' => $achievement->Description,
                'Points' => $achievement->Points,
                'BadgeName' => $achievement->BadgeName,
                'AchievementAuthor' => $achievement->Author,
                'GameID' => $game->ID,
                'GameTitle' => $game->Title,
                'GameIcon' => $game->ImageIcon,
                'ConsoleName' => $system->Name,
                'ReportedAt' => $ticket->ReportedAt->__toString(),
                'ReportType' => 2,
                'ReportTypeDescription' => 'Did not trigger',
                'ReportState' => 1,
                'ReportStateDescription' => 'Open',
                'Hardcore' => 1,
                'ReportNotes' => $ticket->ReportNotes,
                'ReportedBy' => $this->user->User,
                'ResolvedAt' => null,
                'ResolvedBy' => null,
                'URL' => config('app.url') . '/ticketmanager.php?i=' . $ticket->ID,
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
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create(['AchievementID' => $achievement->ID, 'ReportedByUserID' => $this->user->ID]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game2->ID]);
        /** @var Ticket $ticket2 */
        $ticket2 = Ticket::factory()->create(['AchievementID' => $achievement2->ID,
            'ReportedByUserID' => $user2->ID, 'Hardcore' => 0, 'ReportType' => TicketType::TriggeredAtWrongTime]);
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game3->ID]);
        Ticket::factory()->create(['AchievementID' => $achievement3->ID,
            'ReportedByUserID' => $user2->ID, 'ReportState' => TicketState::Resolved,
            'ResolvedByUserID' => $user3->ID, 'ResolvedAt' => Carbon::now()]);

        $this->get($this->apiUrl('GetTicketData'))
            ->assertSuccessful()
            ->assertJson([
                'RecentTickets' => [ // tickets returned newest id first (open only)
                    [
                        'ID' => $ticket2->ID,
                        'AchievementID' => $achievement2->ID,
                        'AchievementTitle' => $achievement2->Title,
                        'AchievementDesc' => $achievement2->Description,
                        'Points' => $achievement2->Points,
                        'BadgeName' => $achievement2->BadgeName,
                        'AchievementAuthor' => $achievement2->Author,
                        'GameID' => $game2->ID,
                        'GameTitle' => $game2->Title,
                        'GameIcon' => $game2->ImageIcon,
                        'ConsoleName' => $system->Name,
                        'ReportedAt' => $ticket2->ReportedAt->__toString(),
                        'ReportType' => 1,
                        'ReportTypeDescription' => 'Triggered at the wrong time',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 0,
                        'ReportNotes' => $ticket2->ReportNotes,
                        'ReportedBy' => $user2->User,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                    ],
                    [
                        'ID' => $ticket->ID,
                        'AchievementID' => $achievement->ID,
                        'AchievementTitle' => $achievement->Title,
                        'AchievementDesc' => $achievement->Description,
                        'Points' => $achievement->Points,
                        'BadgeName' => $achievement->BadgeName,
                        'AchievementAuthor' => $achievement->Author,
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'GameIcon' => $game->ImageIcon,
                        'ConsoleName' => $system->Name,
                        'ReportedAt' => $ticket->ReportedAt->__toString(),
                        'ReportType' => 2,
                        'ReportTypeDescription' => 'Did not trigger',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 1,
                        'ReportNotes' => $ticket->ReportNotes,
                        'ReportedBy' => $this->user->User,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                    ],
                ],
                'OpenTickets' => 2,
                'URL' => config('app.url') . '/ticketmanager.php',
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
            Ticket::factory()->create(['AchievementID' => $achievements->get($i)->ID, 'ReportedByUserId' => $this->user->ID]);
        }
        for ($i = 0; $i < 5; $i++) {
            Ticket::factory()->create(['AchievementID' => $achievements2->get($i)->ID, 'ReportedByUserId' => $this->user->ID]);
        }
        for ($i = 0; $i < 3; $i++) {
            Ticket::factory()->create(['AchievementID' => $achievements3->get($i)->ID, 'ReportedByUserId' => $this->user->ID]);
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
                'URL' => config('app.url') . '/ticketmanager.php?f=1',
            ]);
    }

    public function testGetTicketDataForUser(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $this->user->User]);
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create(['AchievementID' => $achievement->ID, 'ReportedByUserID' => $this->user->ID]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Game $game2 */
        $game2 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement2 */
        $achievement2 = Achievement::factory()->published()->create(['GameID' => $game2->ID, 'Author' => $this->user->User]);
        /** @var Ticket $ticket2 */
        $ticket2 = Ticket::factory()->create(['AchievementID' => $achievement2->ID,
            'ReportedByUserID' => $user2->ID, 'Hardcore' => 0, 'ReportType' => TicketType::TriggeredAtWrongTime]);
        /** @var User $user3 */
        $user3 = User::factory()->create();
        /** @var Game $game3 */
        $game3 = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement3 */
        $achievement3 = Achievement::factory()->published()->create(['GameID' => $game3->ID, 'Author' => $this->user->User]);
        Ticket::factory()->create(['AchievementID' => $achievement3->ID,
            'ReportedByUserID' => $user2->ID, 'ReportState' => TicketState::Resolved,
            'ResolvedByUserID' => $user3->ID, 'ResolvedAt' => Carbon::now()]);

        $this->get($this->apiUrl('GetTicketData', ['u' => $this->user->User]))
            ->assertSuccessful()
            ->assertJson([
                'User' => $this->user->User,
                'Open' => 2,
                'Closed' => 0,
                'Resolved' => 1,
                'Total' => 3,
                'URL' => config('app.url') . '/ticketmanager.php?u=' . $this->user->User,
            ]);
    }

    public function testGetTicketDataForGame(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        $achievements = Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID]);

        /** @var Achievement $achievement1 */
        $achievement1 = $achievements->get(0);
        /** @var Achievement $achievement2 */
        $achievement2 = $achievements->get(3);

        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create(['AchievementID' => $achievement1->ID, 'ReportedByUserID' => $this->user->ID]);
        /** @var Ticket $ticket2 */
        $ticket2 = Ticket::factory()->create(['AchievementID' => $achievement2->ID,
            'ReportedByUserID' => $this->user->ID, 'Hardcore' => 0, 'ReportType' => TicketType::TriggeredAtWrongTime]);

        $this->get($this->apiUrl('GetTicketData', ['g' => $game->ID, 'd' => 1]))
            ->assertSuccessful()
            ->assertJson([
                'GameID' => $game->ID,
                'GameTitle' => $game->Title,
                'OpenTickets' => 2,
                'Tickets' => [ // tickets returned newest id first (open only)
                    [
                        'ID' => $ticket2->ID,
                        'AchievementID' => $achievement2->ID,
                        'AchievementTitle' => $achievement2->Title,
                        'AchievementDesc' => $achievement2->Description,
                        'Points' => $achievement2->Points,
                        'BadgeName' => $achievement2->BadgeName,
                        'AchievementAuthor' => $achievement2->Author,
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'GameIcon' => $game->ImageIcon,
                        'ConsoleName' => $system->Name,
                        'ReportedAt' => $ticket2->ReportedAt->__toString(),
                        'ReportType' => 1,
                        'ReportTypeDescription' => 'Triggered at the wrong time',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 0,
                        'ReportNotes' => $ticket2->ReportNotes,
                        'ReportedBy' => $this->user->User,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                    ],
                    [
                        'ID' => $ticket->ID,
                        'AchievementID' => $achievement1->ID,
                        'AchievementTitle' => $achievement1->Title,
                        'AchievementDesc' => $achievement1->Description,
                        'Points' => $achievement1->Points,
                        'BadgeName' => $achievement1->BadgeName,
                        'AchievementAuthor' => $achievement1->Author,
                        'GameID' => $game->ID,
                        'GameTitle' => $game->Title,
                        'GameIcon' => $game->ImageIcon,
                        'ConsoleName' => $system->Name,
                        'ReportedAt' => $ticket->ReportedAt->__toString(),
                        'ReportType' => 2,
                        'ReportTypeDescription' => 'Did not trigger',
                        'ReportState' => 1,
                        'ReportStateDescription' => 'Open',
                        'Hardcore' => 1,
                        'ReportNotes' => $ticket->ReportNotes,
                        'ReportedBy' => $this->user->User,
                        'ResolvedAt' => null,
                        'ResolvedBy' => null,
                    ],
                ],
                'URL' => config('app.url') . '/ticketmanager.php?g=' . $game->ID,
            ]);
    }

    public function testGetTicketDataForAchievement(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->published()->create(['GameID' => $game->ID, 'Author' => $this->user->User]);
        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create(['AchievementID' => $achievement->ID, 'ReportedByUserID' => $this->user->ID]);
        /** @var User $user2 */
        $user2 = User::factory()->create();
        /** @var Ticket $ticket2 */
        $ticket2 = Ticket::factory()->create(['AchievementID' => $achievement->ID,
            'ReportedByUserID' => $user2->ID, 'Hardcore' => 0, 'ReportType' => TicketType::TriggeredAtWrongTime]);

        $this->get($this->apiUrl('GetTicketData', ['a' => $achievement->ID]))
            ->assertSuccessful()
            ->assertJson([
                'AchievementID' => $achievement->ID,
                'AchievementTitle' => $achievement->Title,
                'AchievementDescription' => $achievement->Description,
                'OpenTickets' => 2,
                'URL' => config('app.url') . '/ticketmanager.php?a=' . $achievement->ID,
            ]);
    }
}
