<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Community\Enums\UserRelationStatus;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\PlayerSession;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);
uses(TestsConnect::class);

class GetFriendListTestHelpers
{
    public static function createUser(Game $game, string $richPresence, Carbon $richPresenceUpdatedAt): User
    {
        return User::factory()->create([
            'rich_presence_game_id' => $game->id,
            'rich_presence' => $richPresence,
            'rich_presence_updated_at' => $richPresenceUpdatedAt,
        ]);
    }

    public static function createSession(User $user, Game $game, string $richPresence, Carbon $richPresenceUpdatedAt): void
    {
        PlayerSession::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'rich_presence' => $richPresence,
            'rich_presence_updated_at' => $richPresenceUpdatedAt,
        ]);
    }

    public static function followUser(User $user, User $userToFollow): void
    {
        UserRelation::create([
            'user_id' => $user->id,
            'related_user_id' => $userToFollow->id,
            'status' => UserRelationStatus::Following,
        ]);
    }

    public static function unfollowUser(User $user, User $userToFollow): void
    {
        UserRelation::create([
            'user_id' => $user->id,
            'related_user_id' => $userToFollow->id,
            'status' => UserRelationStatus::NotFollowing,
        ]);
    }

    public static function blockUser(User $user, User $userToFollow): void
    {
        UserRelation::create([
            'user_id' => $user->id,
            'related_user_id' => $userToFollow->id,
            'status' => UserRelationStatus::Blocked,
        ]);
    }
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::now()->startOfSecond());

    $this->createConnectUser();
});

describe('get followed user activity', function () {
    test('no followed users', function () {
        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [],
            ]);
    });

    test('rich presence from session', function () {
        $game1 = Game::factory()->create();
        $date1 = Carbon::parse('2020-10-02 06:18:11');
        $date2 = Carbon::parse('2024-03-05 17:53:03');
        $user2 = GetFriendListTestHelpers::createUser($game1, 'Running through a forest', $date1);
        GetFriendListTestHelpers::createSession($user2, $game1, 'Titles', $date2);
        GetFriendListTestHelpers::followUser($this->user, $user2);

        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [
                    [
                        'Friend' => $user2->display_name,
                        'AvatarUrl' => $user2->avatar_url,
                        'AvatarUpdatedAt' => 0,
                        'RAPoints' => $user2->points_hardcore,
                        'LastSeen' => 'Titles', // session rp is given preference
                        'LastSeenTime' => $date2->unix(),
                        'LastGameId' => $game1->id,
                        'LastGameTitle' => $game1->title,
                        'LastGameIconUrl' => $game1->badge_url,
                    ],
                ],
            ]);
    });

    test('rich presence without session', function () {
        $game1 = Game::factory()->create();
        $date1 = Carbon::parse('2020-10-02 06:18:11');
        $user2 = GetFriendListTestHelpers::createUser($game1, 'Running through a forest', $date1);
        GetFriendListTestHelpers::followUser($this->user, $user2);

        // some users may not have played since we started tracking sessions
        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [
                    [
                        'Friend' => $user2->display_name,
                        'AvatarUrl' => $user2->avatar_url,
                        'AvatarUpdatedAt' => 0,
                        'RAPoints' => $user2->points_hardcore,
                        'LastSeen' => $user2->rich_presence,
                        'LastSeenTime' => $date1->unix(),
                        'LastGameId' => $game1->id,
                        'LastGameTitle' => $game1->title,
                        'LastGameIconUrl' => $game1->badge_url,
                    ],
                ],
            ]);
    });

    test('no rich presence', function () {
        $date1 = Carbon::parse('2020-10-02 06:18:11');
        $user2 = User::factory()->create(['last_activity_at' => $date1]);
        GetFriendListTestHelpers::followUser($this->user, $user2);

        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [
                    [
                        'Friend' => $user2->display_name,
                        'AvatarUrl' => $user2->avatar_url,
                        'AvatarUpdatedAt' => 0,
                        'RAPoints' => $user2->points_hardcore,
                        'LastSeen' => 'Unknown',
                        'LastSeenTime' => $date1->unix(),
                        'LastGameId' => null,
                        'LastGameTitle' => null,
                        'LastGameIconUrl' => null,
                    ],
                ],
            ]);
    });

    test('multiple friends', function () {
        $game1 = Game::factory()->create();
        $game2 = Game::factory()->create();
        $date1 = Carbon::parse('2020-10-02 06:18:11');
        $date2 = Carbon::parse('2024-03-05 17:53:03');
        $date3 = Carbon::parse('2024-05-27 13:36:42');
        $user2 = GetFriendListTestHelpers::createUser($game1, 'Running through a forest', $date1);
        GetFriendListTestHelpers::createSession($user2, $game1, 'Titles', $date2);
        GetFriendListTestHelpers::followUser($this->user, $user2);
        $user3 = GetFriendListTestHelpers::createUser($game2, 'Killing everything', $date3);
        GetFriendListTestHelpers::createSession($user3, $game2, 'Killing everything', $date3);
        GetFriendListTestHelpers::followUser($this->user, $user3);
        $user4 = GetFriendListTestHelpers::createUser($game2, 'Killing everything', $date1);
        GetFriendListTestHelpers::createSession($user3, $game2, 'Killing everything', $date1);
        GetFriendListTestHelpers::followUser($this->user, $user4);
        $user3->avatar_updated_at = $date2;
        $user3->save();

        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [ // newest activity first
                    [
                        'Friend' => $user3->display_name,
                        'AvatarUrl' => media_asset("UserPic/{$user3->username}.png"), // version not included in avatar url
                        'AvatarUpdatedAt' => $date2->unix(),
                        'RAPoints' => $user3->points_hardcore,
                        'LastSeen' => $user3->rich_presence,
                        'LastSeenTime' => $date3->unix(),
                        'LastGameId' => $game2->id,
                        'LastGameTitle' => $game2->title,
                        'LastGameIconUrl' => $game2->badge_url,
                    ],
                    [
                        'Friend' => $user2->display_name,
                        'AvatarUrl' => $user2->avatar_url,
                        'AvatarUpdatedAt' => 0,
                        'RAPoints' => $user2->points_hardcore,
                        'LastSeen' => 'Titles', // session rp is given preference
                        'LastSeenTime' => $date2->unix(),
                        'LastGameId' => $game1->id,
                        'LastGameTitle' => $game1->title,
                        'LastGameIconUrl' => $game1->badge_url,
                    ],
                    [
                        'Friend' => $user4->display_name,
                        'AvatarUrl' => $user4->avatar_url,
                        'AvatarUpdatedAt' => 0,
                        'RAPoints' => $user4->points_hardcore,
                        'LastSeen' => $user4->rich_presence,
                        'LastSeenTime' => $date1->unix(),
                        'LastGameId' => $game2->id,
                        'LastGameTitle' => $game2->title,
                        'LastGameIconUrl' => $game2->badge_url,
                    ],
                ],
            ]);
    });

    test('banned user is not returned', function () {
        $game1 = Game::factory()->create();
        $date1 = Carbon::parse('2020-10-02 06:18:11');
        $user2 = GetFriendListTestHelpers::createUser($game1, 'Running through a forest', $date1);
        GetFriendListTestHelpers::followUser($this->user, $user2);

        $user2->Permissions = Permissions::Banned;
        $user2->banned_at = Carbon::now()->subDays(8);
        $user2->save();

        // some users may not have played since we started tracking sessions
        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [],
            ]);
    });

    test('unfollowed user is not returned', function () {
        $game1 = Game::factory()->create();
        $date1 = Carbon::parse('2020-10-02 06:18:11');
        $user2 = GetFriendListTestHelpers::createUser($game1, 'Running through a forest', $date1);
        GetFriendListTestHelpers::unfollowUser($user2, $this->user);

        // some users may not have played since we started tracking sessions
        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [],
            ]);
    });

    test('blocked user is not returned', function () {
        $game1 = Game::factory()->create();
        $date1 = Carbon::parse('2020-10-02 06:18:11');
        $user2 = GetFriendListTestHelpers::createUser($game1, 'Running through a forest', $date1);
        GetFriendListTestHelpers::blockUser($user2, $this->user);

        // some users may not have played since we started tracking sessions
        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [],
            ]);
    });

    test('user following caller is not returned', function () {
        $game1 = Game::factory()->create();
        $date1 = Carbon::parse('2020-10-02 06:18:11');
        $user2 = GetFriendListTestHelpers::createUser($game1, 'Running through a forest', $date1);
        GetFriendListTestHelpers::followUser($user2, $this->user);

        // some users may not have played since we started tracking sessions
        $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(200)
            ->assertExactJson([
                'Success' => true,
                'Friends' => [],
            ]);
    });
});

describe('validation', function () {
    test('sql error does not leak sql', function () {
        // change the column name to force a query failure
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('rich_presence', 'broken');
        });

        $response = $this->get($this->apiUrl('getfriendlist'))
            ->assertStatus(500)
            ->assertJson([
                'Success' => false,
                'Status' => 500,
            ]);

        $jsonResponse = json_decode($response->getContent());
        $errorMessage = $jsonResponse->Error;

        // Error message should be similar to "SQLSTATE[HY000]: General error: 1 no such column ..."
        // check for "error" to ensure it's not empty
        $this->assertStringContainsString('error', $errorMessage);

        // Then make sure there's no SQL-like text in the message
        $this->assertStringNotContainsStringIgnoringCase('SELECT', $errorMessage);
        $this->assertStringNotContainsStringIgnoringCase('UPDATE', $errorMessage);
        $this->assertStringNotContainsStringIgnoringCase('INSERT', $errorMessage);
        $this->assertStringNotContainsStringIgnoringCase('FROM', $errorMessage);
        $this->assertStringNotContainsStringIgnoringCase('WHERE', $errorMessage);
    });
});
