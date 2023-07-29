<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimType;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\System;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
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

        $view = $this->blade('<x-platform.cards.game gameId="1" />');

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

        insertClaim(
            $user->User,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
            Permissions::Developer,
        );

        // Act
        $view = $this->blade('<x-platform.cards.game gameId="1" />');

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

        insertClaim(
            $user1->User,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
            Permissions::Developer,
        );

        insertClaim(
            $user2->User,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
            Permissions::Developer,
        );

        // Act
        $view = $this->blade('<x-platform.cards.game gameId="1" />');

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

        insertClaim(
            $user1->User,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
            Permissions::Developer,
        );

        insertClaim(
            $user2->User,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
            Permissions::Developer,
        );

        insertClaim(
            $user3->User,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
            Permissions::Developer,
        );

        // Act
        $view = $this->blade('<x-platform.cards.game gameId="1" />');

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

        $view = $this->blade('<x-platform.cards.game gameId="1" />');

        $view->assertSeeText($game->Title);
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText('Achievements');
        $view->assertSeeText('6');
        $view->assertSeeText('Points');
        $view->assertSeeText('30');
        $view->assertSeeText('Retro Ratio');
        $view->assertSeeText('Last Updated');
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

        insertClaim(
            $user->User,
            $game->ID,
            ClaimType::Primary,
            ClaimSetType::NewSet,
            ClaimSpecial::None,
            Permissions::Developer,
        );

        // Act
        $view = $this->blade('<x-platform.cards.game gameId="1" />');

        // Assert
        $view->assertSeeText($game->Title);
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText('Achievements');
        $view->assertSeeText('6');
        $view->assertSeeText('Points');
        $view->assertSeeText('30');
        $view->assertSeeText('Retro Ratio');
        $view->assertSeeText("Revision in progress");
        $view->assertSeeText("by " . $user->User);
    }

    public function testItRendersRetiredGame(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        Game::factory()->create(['ID' => 1, 'ConsoleID' => $system->ID, 'Title' => 'Pokemon Blue Version ~Z~']);

        $view = $this->blade('<x-platform.cards.game gameId="1" />');

        $view->assertSeeText("Pokemon Blue Version");
        $view->assertSeeText($system->ConsoleName);
        $view->assertSeeText("has been retired");
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
        $view = $this->blade('<x-platform.cards.game gameId="1" targetUsername="AAA" />');

        // Assert
        $view->assertSeeText('Completed 2 July 2015');
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
        $view = $this->blade('<x-platform.cards.game gameId="1" targetUsername="AAA" />');

        // Assert
        $view->assertSeeText('Mastered 2 July 2015');
    }
}
