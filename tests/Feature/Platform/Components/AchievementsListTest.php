<?php

declare(strict_types=1);

use App\Platform\Enums\AchievementType;
use App\Platform\Models\Achievement;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\TestCase;

class AchievementsListTest extends TestCase
{
    use RefreshDatabase;
    use TestsPlayerAchievements;

    public function testItRendersWithNoAchievements(): void
    {
        $view = $this->blade('<x-game.achievements-list.root :achievements="$achievements" :totalPlayerCount="$totalPlayerCount" />', [
            'achievements' => [],
            'totalPlayerCount' => 0,
        ]);

        $view->assertSeeText("no achievements");
    }

    public function testItRendersAllAchievementsInCorrectOrder(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();

        $achievementOne = Achievement::factory()->create(['Title' => 'One', 'DisplayOrder' => 0]);
        $achievementTwo = Achievement::factory()->create(['Title' => 'Two', 'DisplayOrder' => 1]);
        $achievementThree = Achievement::factory()->create(['Title' => 'Three', 'DisplayOrder' => 2]);
        $achievementFour = Achievement::factory()->create(['Title' => 'Four', 'DisplayOrder' => 3]);

        $this->addHardcoreUnlock($user, $achievementThree);

        $achievementThree = $achievementThree->toArray();
        $achievementThree['DateEarnedHardcore'] = Carbon::now();

        // Act
        $view = $this->blade('<x-game.achievements-list.root :achievements="$achievements" :totalPlayerCount="$totalPlayerCount" />', [
            'achievements' => compact('achievementOne', 'achievementTwo', 'achievementThree', 'achievementFour'),
            'totalPlayerCount' => 1000,
        ]);

        // Assert
        $view->assertSeeTextInOrder(['Three', 'One', 'Two', 'Four']);
    }

    public function testItRendersMetadata(): void
    {
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create(['type' => AchievementType::Progression, 'TrueRatio' => 5000]);

        $view = $this->blade('<x-game.achievements-list.root :achievements="$achievements" :totalPlayerCount="$totalPlayerCount" />', [
            'achievements' => compact('achievement'),
            'totalPlayerCount' => 1000,
        ]);

        $view->assertSeeText($achievement->Title);
        $view->assertSeeText($achievement->Description);
        $view->assertSeeText($achievement->Points);
        $view->assertSeeText("5,000");
        $view->assertSeeText("Progression");
    }

    public function testUnlockedRowsHaveCorrectClassName(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create(['type' => AchievementType::Progression, 'TrueRatio' => 5000]);

        $this->addHardcoreUnlock($user, $achievement);
        $achievement = $achievement->toArray();
        $achievement['DateEarnedHardcore'] = Carbon::now();

        // Act
        $view = $this->blade('<x-game.achievements-list.root :achievements="$achievements" :totalPlayerCount="$totalPlayerCount" />', [
            'achievements' => compact('achievement'),
            'totalPlayerCount' => 1000,
        ]);

        // Assert
        $view->assertSee('unlocked-row');
    }

    public function testItRendersfOnlyOneWinConditionCorrectly(): void
    {
        /** @var Achievement $achievementOne */
        $achievementOne = Achievement::factory()->create(['type' => AchievementType::Progression, 'DisplayOrder' => 0]);
        /** @var Achievement $achievementTwo */
        $achievementTwo = Achievement::factory()->create(['type' => AchievementType::WinCondition, 'DisplayOrder' => 1]);

        $view = $this->blade('<x-game.achievements-list.root :achievements="$achievements" :totalPlayerCount="$totalPlayerCount" />', [
            'achievements' => compact('achievementOne', 'achievementTwo'),
            'totalPlayerCount' => 1000,
        ]);

        $view->assertSeeTextInOrder(['Progression', 'Win Condition']);
    }

    public function testItRendersAuthorNameIfInstructed(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create(['Author' => $user->User]);

        $view = $this->blade('<x-game.achievements-list.root :achievements="$achievements" :showAuthorNames="$showAuthorNames" />', [
            'achievements' => compact('achievement'),
            'showAuthorNames' => true,
        ]);

        $view->assertSeeTextInOrder(['Author', $user->User]);
    }
}
