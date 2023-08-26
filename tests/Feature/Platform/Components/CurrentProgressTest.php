<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentProgressTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersNothingIfNoAchievements(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="false"
                :isBeatenHardcore="false"
                :isBeatenSoftcore="false"
                :isCompleted="false"
                :isMastered="false"
                :numEarnedHardcoreAchievements="0"
                :numEarnedHardcorePoints="0"
                :numEarnedSoftcoreAchievements="0"
                :numEarnedSoftcorePoints="0"
                :numEarnedWeightedPoints="0"
                :totalAchievementsCount="0"
                :totalPointsCount="0"
            />
        HTML);

        $view->assertDontSeeText("Your Progress");
    }

    public function testItRendersEmptyStateIfNothingUnlocked(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="false"
                :isBeatenHardcore="false"
                :isBeatenSoftcore="false"
                :isCompleted="false"
                :isMastered="false"
                :numEarnedHardcoreAchievements="0"
                :numEarnedHardcorePoints="0"
                :numEarnedSoftcoreAchievements="0"
                :numEarnedSoftcorePoints="0"
                :numEarnedWeightedPoints="0"
                :totalAchievementsCount="100"
                :totalPointsCount="1000"
            />
        HTML);

        $view->assertSeeText("Your Progress");
        $view->assertSeeText("any achievements for this game");
    }

    public function testItRendersOnlyHardcoreUserProgress(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="false"
                :isBeatenHardcore="false"
                :isBeatenSoftcore="false"
                :isCompleted="false"
                :isMastered="false"
                :numEarnedHardcoreAchievements="20"
                :numEarnedHardcorePoints="200"
                :numEarnedSoftcoreAchievements="0"
                :numEarnedSoftcorePoints="0"
                :numEarnedWeightedPoints="1247"
                :totalAchievementsCount="100"
                :totalPointsCount="1000"
            />
        HTML);

        $view->assertSeeText("Unfinished");
        $view->assertSeeTextInOrder(["20", "of 100 achievements"]);
        $view->assertSeeTextInOrder(["200", "(1,247)", "of 1,000 points"]);
        $view->assertSeeText("20% complete");
    }

    public function testItRendersOnlySoftcoreUserProgress(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="false"
                :isBeatenHardcore="false"
                :isBeatenSoftcore="false"
                :isCompleted="false"
                :isMastered="false"
                :numEarnedHardcoreAchievements="0"
                :numEarnedHardcorePoints="0"
                :numEarnedSoftcoreAchievements="20"
                :numEarnedSoftcorePoints="200"
                :numEarnedWeightedPoints="0"
                :totalAchievementsCount="100"
                :totalPointsCount="1000"
            />
        HTML);

        $view->assertSeeText("Unfinished");
        $view->assertSeeTextInOrder(["20", "of 100 softcore achievements"]);
        $view->assertSeeTextInOrder(["200", "of 1,000 softcore points"]);
        $view->assertSeeText("20% complete");
    }

    public function testItRendersCombinedHardcoreAndSoftcoreUserProgress(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="false"
                :isBeatenHardcore="false"
                :isBeatenSoftcore="false"
                :isCompleted="false"
                :isMastered="false"
                :numEarnedHardcoreAchievements="20"
                :numEarnedHardcorePoints="200"
                :numEarnedSoftcoreAchievements="4"
                :numEarnedSoftcorePoints="50"
                :numEarnedWeightedPoints="568"
                :totalAchievementsCount="100"
                :totalPointsCount="1000"
            />
        HTML);

        $view->assertSeeText("Unfinished");
        $view->assertSeeTextInOrder(["20", "hardcore achievements"]);
        $view->assertSeeTextInOrder(["200", "(568)", "hardcore points"]);
        $view->assertSeeTextInOrder(["4", "softcore achievements"]);
        $view->assertSeeTextInOrder(["50", "softcore points"]);
        $view->assertSeeText("24% complete");
    }

    public function testItReportsGameAsBeatenSoftcore(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="true"
                :isBeatenHardcore="false"
                :isBeatenSoftcore="true"
                :isCompleted="false"
                :isMastered="false"
                :numEarnedHardcoreAchievements="20"
                :numEarnedHardcorePoints="200"
                :numEarnedSoftcoreAchievements="4"
                :numEarnedSoftcorePoints="50"
                :numEarnedWeightedPoints="568"
                :totalAchievementsCount="100"
                :totalPointsCount="1000"
            />
        HTML);

        $view->assertSeeText("Beaten (softcore)");
    }

    public function testItReportsGameAsBeatenHardcore(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="true"
                :isBeatenHardcore="true"
                :isBeatenSoftcore="true"
                :isCompleted="false"
                :isMastered="false"
                :numEarnedHardcoreAchievements="20"
                :numEarnedHardcorePoints="200"
                :numEarnedSoftcoreAchievements="4"
                :numEarnedSoftcorePoints="50"
                :numEarnedWeightedPoints="568"
                :totalAchievementsCount="100"
                :totalPointsCount="1000"
            />
        HTML);

        $view->assertDontSeeText("Beaten (softcore)");
        $view->assertSeeText("Beaten");
    }

    public function testItReportsGameAsCompleted(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="true"
                :isBeatenHardcore="true"
                :isBeatenSoftcore="true"
                :isCompleted="true"
                :isMastered="false"
                :numEarnedHardcoreAchievements="20"
                :numEarnedHardcorePoints="200"
                :numEarnedSoftcoreAchievements="4"
                :numEarnedSoftcorePoints="50"
                :numEarnedWeightedPoints="568"
                :totalAchievementsCount="100"
                :totalPointsCount="1000"
            />
        HTML);

        $view->assertDontSeeText("Beaten");
        $view->assertSeeText("Completed");
    }

    public function testItReportsGameAsMastered(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="true"
                :isBeatenHardcore="true"
                :isBeatenSoftcore="true"
                :isCompleted="true"
                :isMastered="true"
                :numEarnedHardcoreAchievements="20"
                :numEarnedHardcorePoints="200"
                :numEarnedSoftcoreAchievements="4"
                :numEarnedSoftcorePoints="50"
                :numEarnedWeightedPoints="568"
                :totalAchievementsCount="100"
                :totalPointsCount="1000"
            />
        HTML);

        $view->assertDontSeeText("Completed");
        $view->assertSeeText("Mastered");
    }

    public function testItReportsGameAsUnbeaten(): void
    {
        $view = $this->blade(<<<HTML
            <x-game.current-progress.root
                :isBeatable="true"
                :isBeatenHardcore="false"
                :isBeatenSoftcore="false"
                :isCompleted="true"
                :isMastered="true"
                :numEarnedHardcoreAchievements="20"
                :numEarnedHardcorePoints="200"
                :numEarnedSoftcoreAchievements="4"
                :numEarnedSoftcorePoints="50"
                :numEarnedWeightedPoints="568"
                :totalAchievementsCount="100"
                :totalPointsCount="1000"
            />
        HTML);

        $view->assertDontSeeText("Mastered");
        $view->assertSeeText("Unbeaten");
    }
}
