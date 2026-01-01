<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Components;

use App\Community\Enums\Rank;
use App\Enums\Permissions;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCardTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersRegisteredUserData(): void
    {
        User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 5000,
            'points' => 50,
            'points_weighted' => 6500,
            'Untracked' => false,
            'Permissions' => Permissions::Registered,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-user-card user="mockUser" />');

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
        $this->seed(RolesTableSeeder::class);

        /** @var User $user */
        $user = User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 5000,
            'points' => 50,
            'points_weighted' => 6500,
            'Untracked' => false,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);
        $user->assignRole(Role::DEVELOPER_JUNIOR);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText('Junior Developer');
    }

    public function testItDoesntDisplayIfUserIsBanned(): void
    {
        User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 5000,
            'points' => 50,
            'points_weighted' => 6500,
            'Untracked' => false,
            'Permissions' => Permissions::Banned,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertDontSeeText(Permissions::toString(Permissions::Banned));
    }

    public function testItShowsSoftcoreStandingsWhenAppropriate(): void
    {
        User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 50,
            'points' => 5000,
            'points_weighted' => 6500,
            'Untracked' => false,
            'Permissions' => Permissions::Banned,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText("Softcore Points");
        $view->assertSeeText("5,000");
    }

    public function testItSaysIfUserIsUntracked(): void
    {
        User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 5000,
            'points' => 50,
            'points_weighted' => 6500,
            'Untracked' => true,
            'Permissions' => Permissions::Banned,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText("Untracked");
    }

    public function testItSaysIfUserDoesntMeetRankMinimumPointsThreshold(): void
    {
        User::factory()->create([
            'username' => 'mockUser',
            'motto' => 'mockMotto',
            'points_hardcore' => 1,
            'points' => 1,
            'points_weighted' => 1,
            'Untracked' => false,
            'Permissions' => Permissions::Banned,
            'created_at' => '2023-07-01 00:00:00',
            'last_activity_at' => '2023-07-10 00:00:00',
        ]);

        $view = $this->blade('<x-user-card user="mockUser" />');

        $view->assertSeeText("Needs at least " . Rank::MIN_POINTS . " points");
    }
}
