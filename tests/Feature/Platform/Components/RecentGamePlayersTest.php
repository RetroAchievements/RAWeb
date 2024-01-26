<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Components;

use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecentGamePlayersTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersRichPresenceMessages(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $mockDate = Carbon::now();
        $recentPlayerData = [
            [
                'User' => $user->User,
                'Date' => $mockDate,
                'Activity' => 'Playing Sonic the Hedgehog',
                'NumAwarded' => 10,
                'NumAwardedHardcore' => 9,
                'NumAchievements' => 12,
            ],
        ];

        $view = $this->blade('
            <x-game.recent-game-players
                :recentPlayerData="$recentPlayerData"
                gameTitle="Sonic the Hedgehog"
            />
        ', [
            'recentPlayerData' => $recentPlayerData,
        ]);

        $view->assertSee($user->User);
        $view->assertSeeText('Playing Sonic the Hedgehog');
        $view->assertSeeText($mockDate->format('d M Y, g:ia'));
    }

    public function testItRendersBrokenRichPresenceMessages(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $recentPlayerData = [
            [
                'User' => $user->User,
                'Date' => Carbon::now(),
                'Activity' => 'Unknown macro this should not appear',
                'NumAwarded' => 10,
                'NumAwardedHardcore' => 9,
                'NumAchievements' => 12,
            ],
        ];

        $view = $this->blade('
            <x-game.recent-game-players
                :recentPlayerData="$recentPlayerData"
                gameTitle="Sonic the Hedgehog"
            />
        ', [
            'recentPlayerData' => $recentPlayerData,
        ]);

        $view->assertDontSeeText("this should not appear");
        $view->assertSeeTextInOrder(["⚠️", "Playing Sonic the Hedgehog"]);
    }
}
