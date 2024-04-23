<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Support\Shortcode\Shortcode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortcodeEmbedTest extends TestCase
{
    use RefreshDatabase;

    public function testStripAndClampGame(): void
    {
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1, 'Name' => 'Mega Drive']);

        Game::factory()->create([
            'ID' => 1,
            'Title' => 'Sonic the Hedgehog',
            'ConsoleID' => $system->ID,
        ]);

        $this->assertSame(
            'Sonic the Hedgehog (Mega Drive)',
            Shortcode::stripAndClamp('[game=1]')
        );
    }

    public function testStripAndClampAchievement(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);
        /** @var User $user */
        $user = User::factory()->create();

        Achievement::factory()->published()->create([
            'ID' => 1,
            'GameID' => $game->ID,
            'Title' => 'Ring Collector',
            'Points' => 5,
            'user_id' => $user->id,
        ]);

        $this->assertSame(
            'Ring Collector (5)',
            Shortcode::stripAndClamp('[ach=1]')
        );
    }

    public function testStripAndClampNonExistentGamesAndAchievements(): void
    {
        $this->assertSame(
            'is great',
            Shortcode::stripAndClamp('[game=999999] is great')
        );

        $this->assertSame(
            'is difficult',
            Shortcode::stripAndClamp('[ach=999999] is difficult')
        );
    }

    public function testStripAndClampGameTitlesWithSpecialCharacters(): void
    {
        /** @var System $system */
        $system = System::factory()->create(['ID' => 100, 'Name' => 'Hubs']);

        Game::factory()->create([
            'ID' => 1,
            'Title' => '[Series - Star Wars]',
            'ConsoleID' => $system->ID,
        ]);

        $this->assertSame(
            'I like [Series - Star Wars] (Hubs).',
            Shortcode::stripAndClamp('I like [game=1].')
        );
    }

    public function testStripAndClampMultipleShortcodes(): void
    {
        /** @var System $system */
        $system = System::factory()->create(['ID' => 100, 'Name' => 'Hubs']);

        /** @var Game $game */
        $game = Game::factory()->create([
            'ID' => 1,
            'Title' => '[Series - Star Wars]',
            'ConsoleID' => $system->ID,
        ]);

        /** @var User $user */
        $user = User::factory()->create();

        Achievement::factory()->published()->create([
            'ID' => 1,
            'GameID' => $game->ID,
            'Title' => 'Finish the Game [100% Completion]',
            'Points' => 50,
            'user_id' => $user->id,
        ]);

        $this->assertSame(
            'Developed [Series - Star Wars] (Hubs), Achieved Finish the Game [100% Completion] (50)',
            Shortcode::stripAndClamp('Developed [game=1], Achieved [ach=1]')
        );
    }
}
