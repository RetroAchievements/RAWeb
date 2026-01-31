<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Actions;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\CommentableType;
use App\Enums\Permissions;
use App\Mail\RequestAccountDeleteMail;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\User;
use App\Platform\Actions\RequestAccountDeletionAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Platform\Concerns\TestsAuditComments;
use Tests\TestCase;

class RequestAccountDeletionActionTest extends TestCase
{
    use RefreshDatabase;

    use TestsAuditComments;

    public function testDeleteUnregistered(): void
    {
        $this->addServerUser();
        Mail::fake();

        $now = Carbon::parse('2020-05-05 4:23:16');
        Carbon::setTestNow($now);

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Unregistered]);

        $this->assertTrue((new RequestAccountDeletionAction())->execute($user));
        $user->refresh();

        $this->assertEquals(Permissions::Unregistered, $user->getAttribute('Permissions'));
        $this->assertEquals($now, $user->delete_requested_at);

        $this->assertAuditComment(CommentableType::UserModeration, $user->id, $user->username . ' requested account deletion');

        Mail::assertQueued(RequestAccountDeleteMail::class, $user->email);

        /* second attempt to delete user does nothing */
        $now2 = $now->clone()->addDays(2);
        Carbon::setTestNow($now2);

        $this->assertFalse((new RequestAccountDeletionAction())->execute($user));

        $user->refresh();
        $this->assertEquals($now, $user->delete_requested_at);
    }

    public function testDeleteDeveloperWithClaims(): void
    {
        $this->addServerUser();
        Mail::fake();

        $now = Carbon::parse('2020-05-05 4:23:16');
        Carbon::setTestNow($now);

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Developer]);

        $game1 = $this->seedGame(withHash: false);
        $game2 = $this->seedGame(withHash: false);
        $game3 = $this->seedGame(withHash: false);

        $claim1 = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game1->id,
        ]);

        $claim2 = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game2->id,
            'claim_type' => ClaimType::Collaboration,
            'set_type' => ClaimSetType::Revision,
        ]);

        $claim3 = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game3->id,
            'status' => ClaimStatus::Complete,
        ]);

        $this->assertTrue((new RequestAccountDeletionAction())->execute($user));
        $user->refresh();

        // account should be demoted to Registered
        $this->assertEquals(Permissions::Registered, $user->getAttribute('Permissions'));
        $this->assertEquals($now, $user->delete_requested_at);

        $this->assertAuditComment(CommentableType::UserModeration, $user->id, $user->username . ' requested account deletion');

        Mail::assertQueued(RequestAccountDeleteMail::class, $user->email);

        // non-completed claims should be dropped
        $claim1->refresh();
        $this->assertEquals(ClaimStatus::Dropped, $claim1->status);
        $this->assertAuditComment(CommentableType::SetClaim, $claim1->id, $user->username . "'s Primary claim dropped via demotion to Registered.");

        $claim2->refresh();
        $this->assertEquals(ClaimStatus::Dropped, $claim2->status);
        $this->assertAuditComment(CommentableType::SetClaim, $claim2->id, $user->username . "'s Collaboration claim dropped via demotion to Registered.");

        $claim3->refresh();
        $this->assertEquals(ClaimStatus::Complete, $claim3->status);
    }

    public function testCleansUpOldDeletionCommentsOnSubsequentRequest(): void
    {
        $this->addServerUser();
        Mail::fake();

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Registered]);

        // ... simulate the first request/cancel cycle ...
        $firstRequest = Comment::factory()->create([
            'commentable_type' => CommentableType::UserModeration,
            'commentable_id' => $user->id,
            'user_id' => Comment::SYSTEM_USER_ID,
            'body' => $user->display_name . ' requested account deletion',
            'created_at' => now()->subDays(10),
        ]);
        $firstCancel = Comment::factory()->create([
            'commentable_type' => CommentableType::UserModeration,
            'commentable_id' => $user->id,
            'user_id' => Comment::SYSTEM_USER_ID,
            'body' => $user->display_name . ' canceled account deletion',
            'created_at' => now()->subDays(9),
        ]);

        // ... simulate the second request/cancel cycle ...
        $secondRequest = Comment::factory()->create([
            'commentable_type' => CommentableType::UserModeration,
            'commentable_id' => $user->id,
            'user_id' => Comment::SYSTEM_USER_ID,
            'body' => $user->display_name . ' requested account deletion',
            'created_at' => now()->subDays(7),
        ]);
        $secondCancel = Comment::factory()->create([
            'commentable_type' => CommentableType::UserModeration,
            'commentable_id' => $user->id,
            'user_id' => Comment::SYSTEM_USER_ID,
            'body' => $user->display_name . ' canceled account deletion',
            'created_at' => now()->subDays(6),
        ]);

        // ... simulate the third request/cancel cycle ...
        $thirdRequest = Comment::factory()->create([
            'commentable_type' => CommentableType::UserModeration,
            'commentable_id' => $user->id,
            'user_id' => Comment::SYSTEM_USER_ID,
            'body' => $user->display_name . ' requested account deletion',
            'created_at' => now()->subDays(3),
        ]);
        $thirdCancel = Comment::factory()->create([
            'commentable_type' => CommentableType::UserModeration,
            'commentable_id' => $user->id,
            'user_id' => Comment::SYSTEM_USER_ID,
            'body' => $user->display_name . ' canceled account deletion',
            'created_at' => now()->subDays(2),
        ]);

        // ... execute a new deletion request ...
        $this->assertTrue((new RequestAccountDeletionAction())->execute($user));

        // ... the first pair should be kept ...
        $this->assertNull($firstRequest->fresh()->deleted_at);
        $this->assertNull($firstCancel->fresh()->deleted_at);

        // ... the middle pair should be soft deleted ...
        $this->assertNotNull($secondRequest->fresh()->deleted_at);
        $this->assertNotNull($secondCancel->fresh()->deleted_at);

        // ... the last pair should be kept ...
        $this->assertNull($thirdRequest->fresh()->deleted_at);
        $this->assertNull($thirdCancel->fresh()->deleted_at);

        $newRequest = Comment::where('commentable_type', CommentableType::UserModeration)
            ->where('commentable_id', $user->id)
            ->where('user_id', Comment::SYSTEM_USER_ID)
            ->where('body', 'like', '%requested account deletion%')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();

        $this->assertNotNull($newRequest);
        $this->assertNotEquals($firstRequest->id, $newRequest->id);

        // ... the new comment should include the ordinal count ...
        $this->assertStringContainsString('(4th request)', $newRequest->body);
    }
}
