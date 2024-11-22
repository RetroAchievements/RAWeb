<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Action;

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
use App\Models\User;
use App\Platform\Actions\RequestAccountDeletionAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Feature\Concerns\TestsMail;
use Tests\Feature\Platform\Concerns\TestsAuditComments;
use Tests\TestCase;

class RequestAccountDeletionActionTest extends TestCase
{
    use RefreshDatabase;

    use TestsAuditComments;
    use TestsMail;

    public function testDeleteUnregistered(): void
    {
        $this->addServerUser();
        $this->captureEmails();

        $now = Carbon::parse('2020-05-05 4:23:16');
        Carbon::setTestNow($now);

        /** @var User $user */
        $user = User::factory()->create(['Permissions' => Permissions::Unregistered]);

        $this->assertTrue((new RequestAccountDeletionAction())->execute($user));
        $user->refresh();

        $this->assertEquals(Permissions::Unregistered, $user->getAttribute('Permissions'));
        $this->assertEquals($now, $user->DeleteRequested);

        $this->assertAuditComment(ArticleType::UserModeration, $user->ID, $user->User . ' requested account deletion');

        $this->assertEmailSent($user, "Account Deletion Request");

        /* second attempt to delete user does nothing */
        $now2 = $now->clone()->addDays(2);
        Carbon::setTestNow($now2);

        $this->captureEmails();
        $this->assertFalse((new RequestAccountDeletionAction())->execute($user));

        $user->refresh();
        $this->assertEquals($now, $user->DeleteRequested);

        $this->assertEmailNotSent($user);
    }

    public function testDeleteDeveloperWithClaims(): void
    {
        $this->addServerUser();
        $this->captureEmails();

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
            'ClaimType' => ClaimType::Collaboration,
            'SetType' => ClaimSetType::Revision,
        ]);

        $claim3 = AchievementSetClaim::factory()->create([
            'user_id' => $user->id,
            'game_id' => $game3->id,
            'Status' => ClaimStatus::Complete,
        ]);

        $this->assertTrue((new RequestAccountDeletionAction())->execute($user));
        $user->refresh();

        // account should be demoted to Registered
        $this->assertEquals(Permissions::Registered, $user->getAttribute('Permissions'));
        $this->assertEquals($now, $user->DeleteRequested);

        $this->assertAuditComment(ArticleType::UserModeration, $user->ID, $user->User . ' requested account deletion');

        $this->assertEmailSent($user, "Account Deletion Request");

        // non-completed claims should be dropped
        $claim1->refresh();
        $this->assertEquals(ClaimStatus::Dropped, $claim1->Status);
        $this->assertAuditComment(ArticleType::SetClaim, $claim1->ID, $user->User . "'s Primary claim dropped via demotion to Registered.");

        $claim2->refresh();
        $this->assertEquals(ClaimStatus::Dropped, $claim2->Status);
        $this->assertAuditComment(ArticleType::SetClaim, $claim2->ID, $user->User . "'s Collaboration claim dropped via demotion to Registered.");

        $claim3->refresh();
        $this->assertEquals(ClaimStatus::Complete, $claim3->Status);
    }
}
