<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Actions\ClearAccountDataAction;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\UserGameListType;
use App\Community\Enums\UserRelationship;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\PlayerBadge;
use App\Models\Subscription;
use App\Models\System;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Models\UserRelation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Platform\Concerns\TestsPlayerAchievements;
use Tests\Feature\Platform\Concerns\TestsPlayerBadges;
use Tests\TestCase;

class ClearAccountDataTest extends TestCase
{
    use RefreshDatabase;

    use TestsPlayerAchievements;
    use TestsPlayerBadges;

    public function testClearsData(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();

        $this->assertNotEquals('', $user2->EmailAddress);

        UserRelation::create([
            'User' => $user1->User,
            'user_id' => $user1->id,
            'Friend' => $user2->User,
            'related_user_id' => $user2->id,
            'Friendship' => UserRelationship::Following,
        ]);

        UserRelation::create([
            'User' => $user2->User,
            'user_id' => $user2->id,
            'Friend' => $user1->User,
            'related_user_id' => $user1->id,
            'Friendship' => UserRelationship::Following,
        ]);

        UserGameListEntry::create([
            'user_id' => $user2->id,
            'type' => UserGameListType::AchievementSetRequest,
            'GameID' => 1234,
        ]);

        Subscription::create([
            'user_id' => $user2->id,
            'subject_type' => SubscriptionSubjectType::GameWall,
            'subject_id' => 5,
            'state' => true,
        ]);

        $system = System::factory()->create(['ID' => 1]);
        $game = Game::factory()->create(['ConsoleID' => $system->id]);
        $this->addMasteryBadge($user2, $game);

        $thread = MessageThread::create([
            'title' => 'Message',
        ]);
        MessageThreadParticipant::create([
            'user_id' => $user1->id,
            'thread_id' => $thread->id,
        ]);
        MessageThreadParticipant::create([
            'user_id' => $user2->id,
            'thread_id' => $thread->id,
        ]);

        $game = Game::factory()->create();
        $leaderboard = Leaderboard::factory()->create(['GameID' => $game->id]);
        $leaderboardEntry = LeaderboardEntry::factory()->create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user2->id,
        ]);

        $this->assertEquals(1, UserRelation::where('user_id', $user2->id)->count());
        $this->assertEquals(1, UserRelation::where('related_user_id', $user2->id)->count());
        $this->assertEquals(1, UserGameListEntry::where('user_id', $user2->id)->count());
        $this->assertEquals(1, Subscription::where('user_id', $user2->id)->count());
        $this->assertEquals(1, MessageThreadParticipant::where('user_id', $user2->id)->count());
        $this->assertEquals(1, PlayerBadge::where('user_id', $user2->id)->count());

        $action = new ClearAccountDataAction();
        $action->execute($user2);

        $this->assertEquals(0, UserRelation::where('user_id', $user2->id)->count());
        $this->assertEquals(0, UserRelation::where('related_user_id', $user2->id)->count());
        $this->assertEquals(0, UserGameListEntry::where('user_id', $user2->id)->count());
        $this->assertEquals(0, Subscription::where('user_id', $user2->id)->count());
        $this->assertEquals(0, MessageThreadParticipant::where('user_id', $user2->id)->count());
        $this->assertEquals(0, PlayerBadge::where('user_id', $user2->id)->count());
        $this->assertEquals(0, LeaderboardEntry::where('user_id', $user2->id)->count());

        $user2->refresh();
        $this->assertEquals('', $user2->EmailAddress);
    }
}
