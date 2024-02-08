<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Achievement;
use App\Models\Game;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementPoints;
use App\Platform\Enums\AchievementType;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementMoved;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementTypeChanged;
use App\Platform\Events\AchievementUnpublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    public function testItRendersListWithEmptyDatabase(): void
    {
        $this->get('achievementList.php')->assertSuccessful();
    }

    public function testItDispatchedModelEventsOnSpecificAttributeChanges(): void
    {
        Event::fake([
            AchievementCreated::class,
            AchievementPublished::class,
            AchievementUnpublished::class,
            AchievementPointsChanged::class,
            AchievementTypeChanged::class,
            AchievementMoved::class,
        ]);

        $game = Game::factory()->create();

        $achievement = Achievement::factory()->for($game)->create([
            'Points' => 0,
        ]);
        Event::assertDispatched(AchievementCreated::class);

        $achievement->Flags = AchievementFlag::OfficialCore;
        $achievement->save();
        Event::assertDispatched(AchievementPublished::class);

        $achievement->Flags = AchievementFlag::Unofficial;
        $achievement->save();
        Event::assertDispatched(AchievementUnpublished::class);

        $achievement->Points = AchievementPoints::cases()[4];
        $achievement->save();
        Event::assertDispatched(AchievementPointsChanged::class);

        $achievement->type = AchievementType::Progression;
        $achievement->save();
        Event::assertDispatched(AchievementTypeChanged::class);

        $achievement->type = AchievementType::WinCondition;
        $achievement->save();
        Event::assertDispatched(AchievementTypeChanged::class);

        $game2 = Game::factory()->create();
        $achievement->GameID = $game2->id;
        $achievement->save();
        Event::assertDispatched(AchievementMoved::class);
    }
}
