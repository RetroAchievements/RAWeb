<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Components;

use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Models\AchievementSetClaim;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameCardTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersGameWithNoAchievements(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);

        $view = $this->blade('<x-game-card gameId="1" />');

        $view->assertSeeText($game->Title);
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText("No achievements yet.");
    }

    public function testItRendersGameWithActiveClaim(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);

        AchievementSetClaim::factory()->create([
            'User' => $user->User,
            'GameID' => $game->ID,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Special' => ClaimSpecial::None,
            'Status' => ClaimStatus::Active,
        ]);

        // Act
        $view = $this->blade('<x-game-card gameId="1" />');

        // Assert
        $view->assertSeeText($game->Title);
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText("Achievements under development");
        $view->assertSeeText("by " . $user->User);
    }

    public function testItRendersGameWithActiveCollaborationClaim(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        /** @var User $user1 */
        $user1 = User::factory()->create(['Permissions' => Permissions::Developer, 'User' => 'AAA']);
        /** @var User $user2 */
        $user2 = User::factory()->create(['Permissions' => Permissions::Developer, 'User' => 'BBB']);

        AchievementSetClaim::factory()->create([
            'User' => $user1->User,
            'GameID' => $game->ID,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Special' => ClaimSpecial::None,
            'Status' => ClaimStatus::Active,
        ]);

        AchievementSetClaim::factory()->create([
            'User' => $user2->User,
            'GameID' => $game->ID,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Special' => ClaimSpecial::None,
            'Status' => ClaimStatus::Active,
        ]);

        // Act
        $view = $this->blade('<x-game-card gameId="1" />');

        // Assert
        $view->assertSeeText($game->Title);
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText("Achievements under development");
        $view->assertSeeText("by " . $user1->User . " and " . $user2->User);
    }

    public function testItRendersGameWithActiveGroupCollaborationClaim(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        /** @var User $user1 */
        $user1 = User::factory()->create(['Permissions' => Permissions::Developer, 'User' => 'AAA']);
        /** @var User $user2 */
        $user2 = User::factory()->create(['Permissions' => Permissions::Developer, 'User' => 'BBB']);
        /** @var User $user3 */
        $user3 = User::factory()->create(['Permissions' => Permissions::Developer, 'User' => 'CCC']);

        AchievementSetClaim::factory()->create([
            'User' => $user1->User,
            'GameID' => $game->ID,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Special' => ClaimSpecial::None,
            'Status' => ClaimStatus::Active,
        ]);

        AchievementSetClaim::factory()->create([
            'User' => $user2->User,
            'GameID' => $game->ID,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Special' => ClaimSpecial::None,
            'Status' => ClaimStatus::Active,
        ]);

        AchievementSetClaim::factory()->create([
            'User' => $user3->User,
            'GameID' => $game->ID,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Special' => ClaimSpecial::None,
            'Status' => ClaimStatus::Active,
        ]);

        // Act
        $view = $this->blade('<x-game-card gameId="1" />');

        // Assert
        $view->assertSeeText($game->Title);
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText("Achievements under development");
        $view->assertSeeText("by " . $user1->User . ", " . $user2->User . ", and " . $user3->User);
    }

    public function testItRendersGameWithAchievements(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID, 'Points' => 5]);

        $view = $this->blade('<x-game-card gameId="1" />');

        $view->assertSeeText($game->Title);
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText('Achievements');
        $view->assertSeeText('6');
        $view->assertSeeText('Points');
        $view->assertSeeText('30');
        $view->assertSeeText('RetroPoints');
    }

    public function testItRendersGameUndergoingRevision(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID, 'Points' => 5]);

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer, 'User' => 'AAA']);

        AchievementSetClaim::factory()->create([
            'User' => $user->User,
            'GameID' => $game->ID,
            'ClaimType' => ClaimType::Primary,
            'SetType' => ClaimSetType::NewSet,
            'Special' => ClaimSpecial::None,
            'Status' => ClaimStatus::Active,
        ]);

        // Act
        $view = $this->blade('<x-game-card gameId="1" />');

        // Assert
        $view->assertSeeText($game->Title);
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText('Achievements');
        $view->assertSeeText('6');
        $view->assertSeeText('Points');
        $view->assertSeeText('30');
        $view->assertSeeText('RetroPoints');
        $view->assertSeeText("Revision in progress");
        $view->assertSeeText("by " . $user->User);
    }

    public function testItRendersRetiredGame(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID, 'Title' => 'Pokemon Blue Version ~Z~']);

        $view = $this->blade('<x-game-card gameId="1" />');

        $view->assertSeeText("Pokemon Blue Version");
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText("has been retired");
    }

    public function testItRendersSoftcoreBeatenAwards(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['User' => 'AAA']);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID, 'Points' => 5]);

        $awardDate = '2015-07-02 16:44:46';
        PlayerBadge::factory()->create([
            'User' => $user->User,
            'AwardType' => AwardType::GameBeaten,
            'AwardData' => $game->ID,
            'AwardDataExtra' => 0,
            'AwardDate' => $awardDate,
            'DisplayOrder' => 0,
        ]);

        // Act
        $view = $this->blade('<x-game-card gameId="1" targetUsername="AAA" />');

        // Assert
        $view->assertSeeTextInOrder(['Beaten (softcore)',  '2 July 2015']);
    }

    public function testItRendersHardcoreBeatenAwards(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['User' => 'AAA']);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID, 'Points' => 5]);

        $awardDate = '2015-07-02 16:44:46';
        PlayerBadge::factory()->create([
            'User' => $user->User,
            'AwardType' => AwardType::GameBeaten,
            'AwardData' => $game->ID,
            'AwardDataExtra' => 1,
            'AwardDate' => $awardDate,
            'DisplayOrder' => 0,
        ]);

        // Act
        $view = $this->blade('<x-game-card gameId="1" targetUsername="AAA" />');

        // Assert
        $view->assertSeeTextInOrder(['Beaten',  '2 July 2015']);
    }

    public function testItRendersCompletions(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['User' => 'AAA']);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID, 'Points' => 5]);

        $awardDate = '2015-07-02 16:44:46';
        PlayerBadge::factory()->create([
            'User' => $user->User,
            'AwardType' => AwardType::Mastery,
            'AwardData' => $game->ID,
            'AwardDataExtra' => 0,
            'AwardDate' => $awardDate,
            'DisplayOrder' => 0,
        ]);

        // Act
        $view = $this->blade('<x-game-card gameId="1" targetUsername="AAA" />');

        // Assert
        $view->assertSeeTextInOrder(['Completed',  '2 July 2015']);
    }

    public function testItRendersMasteries(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['User' => 'AAA']);
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID, 'Points' => 5]);

        $awardDate = '2015-07-02 16:44:46';
        PlayerBadge::factory()->create([
            'User' => $user->User,
            'AwardType' => AwardType::Mastery,
            'AwardData' => $game->ID,
            'AwardDataExtra' => 1,
            'AwardDate' => $awardDate,
            'DisplayOrder' => 0,
        ]);

        // Act
        $view = $this->blade('<x-game-card gameId="1" targetUsername="AAA" />');

        // Assert
        $view->assertSeeTextInOrder(['Mastered',  '2 July 2015']);
    }

    public function testItRendersEvents(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['User' => 'AAA']);
        /** @var System $system */
        $system = System::factory()->create(['ID' => 101, 'Name' => 'Events']);
        /** @var Game $game */
        $game = Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID]);
        Achievement::factory()->published()->count(6)->create(['GameID' => $game->ID, 'Points' => 5]);

        $awardDate = '2015-07-02 16:44:46';
        PlayerBadge::factory()->create([
            'User' => $user->User,
            'AwardType' => AwardType::Mastery,
            'AwardData' => $game->ID,
            'AwardDataExtra' => 1,
            'AwardDate' => $awardDate,
            'DisplayOrder' => 0,
        ]);

        // Act
        $view = $this->blade('<x-game-card gameId="1" targetUsername="AAA" />');

        // Assert
        $view->assertDontSeeText('Achievements');
        $view->assertDontSeeText('Points');
        $view->assertDontSeeText('Mastered');

        $view->assertSeeText('Events');
        $view->assertSeeTextInOrder(['Awarded',  '2 July 2015']);
    }
}
