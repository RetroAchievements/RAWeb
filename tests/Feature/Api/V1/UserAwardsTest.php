<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\UnlockMode;
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
        PlayerBadge::factory()->count(3)->create(['user_id' => $user->id]);

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
        $visibleAward = PlayerBadge::factory()->create(['DisplayOrder' => 0, 'user_id' => $user->id]);

        // Hidden awards
        PlayerBadge::factory()->create(['DisplayOrder' => -1, 'user_id' => $user->id]);
        PlayerBadge::factory()->create(['DisplayOrder' => -1, 'user_id' => $user->id]);

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
        PlayerBadge::factory()->create(['DisplayOrder' => 0, 'AwardDataExtra' => UnlockMode::Hardcore, 'user_id' => $user->id]);

        // Completion awards
        PlayerBadge::factory()->create(['DisplayOrder' => 0, 'AwardDataExtra' => UnlockMode::Softcore, 'user_id' => $user->id]);
        PlayerBadge::factory()->create(['DisplayOrder' => 0, 'AwardDataExtra' => UnlockMode::Softcore, 'user_id' => $user->id]);

        $this->get($this->apiUrl('GetUserAwards', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'MasteryAwardsCount' => 1,
                'CompletionAwardsCount' => 2,
            ])
            ->assertJsonCount(3, 'VisibleUserAwards');
    }

    public function testGetCorrectBeatenAwardsCount(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Beaten hardcore award
        PlayerBadge::factory()->create(['DisplayOrder' => 0, 'AwardType' => AwardType::GameBeaten, 'AwardDataExtra' => UnlockMode::Hardcore, 'user_id' => $user->id]);

        // Beaten softcore awards
        PlayerBadge::factory()->create(['DisplayOrder' => 0, 'AwardType' => AwardType::GameBeaten, 'AwardDataExtra' => UnlockMode::Softcore, 'user_id' => $user->id]);
        PlayerBadge::factory()->create(['DisplayOrder' => 0, 'AwardType' => AwardType::GameBeaten, 'AwardDataExtra' => UnlockMode::Softcore, 'user_id' => $user->id]);

        $this->get($this->apiUrl('GetUserAwards', ['u' => $user->User]))
            ->assertSuccessful()
            ->assertJson([
                'BeatenHardcoreAwardsCount' => 1,
                'BeatenSoftcoreAwardsCount' => 2,
            ]);
    }

    public function testGetCorrectAwardShape(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->id]);

        $awardDate = '2015-07-02 16:44:46';
        $award = PlayerBadge::factory()->create([
            'user_id' => $user->id,
            'AwardType' => AwardType::Mastery,
            'AwardData' => $game->id,
            'AwardDataExtra' => UnlockMode::Hardcore,
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
