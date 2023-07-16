<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Community\Enums\Rank;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCardTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersRegisteredUserData(): void
    {
        User::factory()->create([
            'User' => 'mockUser',
            'Motto' => 'mockMotto',
            'RAPoints' => 5000,
            'RASoftcorePoints' => 50,
            'TrueRAPoints' => 6500,
            'Untracked' => false,
            'Permissions' => Permissions::Registered,
            'Created' => '2023-07-01 00:00:00',
            'LastLogin' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-community.cards.user user="mockUser" />');

        $view->assertSeeText("mockUser"); // Name
        $view->assertSeeText("mockMotto"); // Motto
        $view->assertSeeText("5,000"); // Points
        $view->assertSeeText("(6,500)"); // RetroPoints
        $view->assertSeeText("#1"); // Site Rank
        $view->assertDontSeeText(Permissions::toString(Permissions::Registered));
        $view->assertDontSeeText("Softcore Points");
    }

    public function testItDisplaysUserRoleWhenAppropriate(): void
    {
        User::factory()->create([
            'User' => 'mockUser',
            'Motto' => 'mockMotto',
            'RAPoints' => 5000,
            'RASoftcorePoints' => 50,
            'TrueRAPoints' => 6500,
            'Untracked' => false,
            'Permissions' => Permissions::JuniorDeveloper,
            'Created' => '2023-07-01 00:00:00',
            'LastLogin' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-community.cards.user user="mockUser" />');

        $view->assertSeeText(Permissions::toString(Permissions::JuniorDeveloper));
    }

    public function testItDoesntDisplayIfUserIsBanned(): void
    {
        User::factory()->create([
            'User' => 'mockUser',
            'Motto' => 'mockMotto',
            'RAPoints' => 5000,
            'RASoftcorePoints' => 50,
            'TrueRAPoints' => 6500,
            'Untracked' => false,
            'Permissions' => Permissions::Banned,
            'Created' => '2023-07-01 00:00:00',
            'LastLogin' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-community.cards.user user="mockUser" />');

        $view->assertDontSeeText(Permissions::toString(Permissions::Banned));
    }

    public function testItShowsSoftcoreStandingsWhenAppropriate(): void
    {
        User::factory()->create([
            'User' => 'mockUser',
            'Motto' => 'mockMotto',
            'RAPoints' => 50,
            'RASoftcorePoints' => 5000,
            'TrueRAPoints' => 6500,
            'Untracked' => false,
            'Permissions' => Permissions::Banned,
            'Created' => '2023-07-01 00:00:00',
            'LastLogin' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-community.cards.user user="mockUser" />');

        $view->assertSeeText("Softcore Points");
        $view->assertSeeText("5,000");
    }

    public function testItSaysIfUserIsUntracked(): void
    {
        User::factory()->create([
            'User' => 'mockUser',
            'Motto' => 'mockMotto',
            'RAPoints' => 5000,
            'RASoftcorePoints' => 50,
            'TrueRAPoints' => 6500,
            'Untracked' => true,
            'Permissions' => Permissions::Banned,
            'Created' => '2023-07-01 00:00:00',
            'LastLogin' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-community.cards.user user="mockUser" />');

        $view->assertSeeText("Untracked");
    }

    public function testItSaysIfUserDoesntMeetRankMinimumPointsThreshold(): void
    {
        User::factory()->create([
            'User' => 'mockUser',
            'Motto' => 'mockMotto',
            'RAPoints' => 1,
            'RASoftcorePoints' => 1,
            'TrueRAPoints' => 1,
            'Untracked' => false,
            'Permissions' => Permissions::Banned,
            'Created' => '2023-07-01 00:00:00',
            'LastLogin' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-community.cards.user user="mockUser" />');

        $view->assertSeeText("Needs at least " . Rank::MIN_POINTS . " points");
    }
}
