<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Components;

use App\Models\Achievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeatenGameCreditDialogTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersCorrectDescriptionLabel(): void
    {
        $achievements = Achievement::factory()->published()->count(4)->create()->toArray();

        $view = $this->blade('
            <x-modal-content.beaten-game-credit
                gameTitle="Sonic the Hedgehog"
                :progressionAchievements="$progressionAchievements"
                :winConditionAchievements="$winConditionAchievements"
            />
        ', [
             'progressionAchievements' => [$achievements[0], $achievements[1]],
             'winConditionAchievements' => [$achievements[2], $achievements[3]],
        ]);

        $view->assertSeeTextInOrder(['Sonic the Hedgehog', '2 progression', '2 win condition']);
    }

    public function testItRendersCorrectDescriptionIfOnlyOneWinCondition(): void
    {
        $achievements = Achievement::factory()->published()->count(4)->create()->toArray();

        $view = $this->blade('
            <x-modal-content.beaten-game-credit
                gameTitle="Sonic the Hedgehog"
                :progressionAchievements="$progressionAchievements"
                :winConditionAchievements="$winConditionAchievements"
            />
        ', [
            'progressionAchievements' => [$achievements[0], $achievements[1], $achievements[2]],
            'winConditionAchievements' => [$achievements[3]],
        ]);

        $view->assertSeeTextInOrder(['Sonic the Hedgehog', '3 progression', '1 win condition']);
    }

    public function testItRendersUnlockedContext(): void
    {
        $achievements = Achievement::factory()->published()->count(4)->create()->toArray();
        $unlockContext = "s:|h:";
        $unlockContext .= $achievements[0]['ID'];
        $unlockContext .= "," . $achievements[1]['ID'];

        $view = $this->blade('
            <x-modal-content.beaten-game-credit
                gameTitle="Sonic the Hedgehog"
                :progressionAchievements="$progressionAchievements"
                :winConditionAchievements="$winConditionAchievements"
                :unlockContext="$unlockContext"
            />
        ', [
            'progressionAchievements' => [$achievements[0], $achievements[1]],
            'winConditionAchievements' => [$achievements[2], $achievements[3]],
            'unlockContext' => $unlockContext,
        ]);

        $view->assertSeeTextInOrder(['Unlocked', 'Unlocked']);
    }
}
