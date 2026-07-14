<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserSetRequestListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsDifferentPersistenceCookieNames(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create(['preferences_bitfield' => 63, 'unread_messages' => 0]);
        $this->actingAs($user);

        // Act
        $generalResponse = $this->get(route('game.request.index'));
        $userResponse = $this->get(route('game.request.user', ['user' => $user->display_name]));

        // Assert
        $generalResponse->assertInertia(fn (Assert $page) => $page
            ->component('games/requests')
            ->where('persistenceCookieName', 'datatable_view_preference_setrequest_general_games')
        );

        $userResponse->assertInertia(fn (Assert $page) => $page
            ->component('games/requests/[user]')
            ->where('persistenceCookieName', 'datatable_view_preference_setrequest_user_games')
        );
    }
}
