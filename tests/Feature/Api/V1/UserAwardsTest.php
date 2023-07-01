<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\AwardType;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserAwardsTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    public function testGetUserAwardsIfNoUserParam(): void
    {
        $this->get($this->apiUrl('GetUserAwards'))
            ->assertStatus(422)
            ->assertJson([
                "message" => "The u field is required.",
            ]);
    }

    public function testGetUserAwardsIfUserNotFound(): void
    {
        $this->get($this->apiUrl('GetUserAwards', ['u' => 'nonExistant']))
            ->assertSuccessful()
            ->assertJson([
                'TotalAwardsCount' => 0,
            ]);
    }

    public function testGetCorrectTotalAwardsCount(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        PlayerBadge::factory()->count(3)->create(['User' => $user->User]);

        $this->get($this->apiUrl('GetUserAwards', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'TotalAwardsCount' => 3,
            ]);
    }

    public function testGetCorrectHiddenAwardsCount(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var PlayerBadge $visibleAward */
        $visibleAward = PlayerBadge::factory()->create(['DisplayOrder' => 0, 'User' => $user->User]);

        // Hidden awards
        PlayerBadge::factory()->create(['DisplayOrder' => -1, 'User' => $user->User]);
        PlayerBadge::factory()->create(['DisplayOrder' => -1, 'User' => $user->User]);

        $this->get($this->apiUrl('GetUserAwards', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'HiddenAwardsCount' => 2,
                'VisibleUserAwards' => [
                    [
                        'AwardType' => 'Mastery/Completion',
                        'DisplayOrder' => 0,
                        'AwardData' => $visibleAward['AwardData'],
                        'AwardDataExtra' => $visibleAward['AwardDataExtra'],
                    ],
                ],
            ]);
    }

    public function testGetCorrectMasteryAwardsCount(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Mastery award
        PlayerBadge::factory()->create(['DisplayOrder' => 0, 'AwardDataExtra' => 1, 'User' => $user->User]);

        // Completion awards
        PlayerBadge::factory()->create(['DisplayOrder' => 0, 'AwardDataExtra' => 0, 'User' => $user->User]);
        PlayerBadge::factory()->create(['DisplayOrder' => 0, 'AwardDataExtra' => 0, 'User' => $user->User]);

        $this->get($this->apiUrl('GetUserAwards', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'MasteryAwardsCount' => 1,
                'CompletionAwardsCount' => 2,
            ])
            ->assertJsonCount(3, 'VisibleUserAwards');
    }

    public function testGetCorrectAwardShape(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $awardDate = '2015-07-02 16:44:46';
        $award = PlayerBadge::factory()->create([
            'User' => $user->User,
            'AwardType' => AwardType::Mastery,
            'AwardData' => $game->ID,
            'AwardDataExtra' => 1,
            'AwardDate' => $awardDate,
            'DisplayOrder' => 0,
        ]);

        $this->get($this->apiUrl('GetUserAwards', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'TotalAwardsCount' => 1,
                'HiddenAwardsCount' => 0,
                'MasteryAwardsCount' => 1,
                'CompletionAwardsCount' => 0,
                'EventAwardsCount' => 0,
                'SiteAwardsCount' => 0,
                'VisibleUserAwards' => [
                    [
                        'AwardedAt' => Carbon::parse($awardDate)->toIso8601String(),
                        'AwardType' => 'Mastery/Completion',
                        'AwardData' => $award['AwardData'],
                        'AwardDataExtra' => $award['AwardDataExtra'],
                        'DisplayOrder' => $award['DisplayOrder'],
                        'Title' => $game['Title'],
                        'ConsoleName' => $system['Name'],
                        'Flags' => $game['Flags'],
                        'ImageIcon' => $game['ImageIcon'],
                    ],
                ],
            ]);
    }
}
