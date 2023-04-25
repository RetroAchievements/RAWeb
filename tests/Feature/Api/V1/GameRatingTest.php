<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LegacyApp\Community\Enums\RatingType;
use LegacyApp\Community\Models\Rating;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Platform\Models\System;
use LegacyApp\Site\Models\User;
use Tests\TestCase;

class GameRatingTest extends TestCase
{
    use RefreshDatabase;
    use BootstrapsApiV1;

    private function addGameRating(Game &$game, User &$user, int $rating): void
    {
        $ratingModel = new Rating([
            'User' => $user->User,
            'RatingObjectType' => RatingType::Game,
            'RatingID' => $game->ID,
            'RatingValue' => $rating,
        ]);
        $game->ratings()->save($ratingModel);
    }

    private function addAchievementRating(Game &$game, User &$user, int $rating): void
    {
        $ratingModel = new Rating([
            'User' => $user->User,
            'RatingObjectType' => RatingType::Achievement,
            'RatingID' => $game->ID,
            'RatingValue' => $rating,
        ]);
        $game->ratings()->save($ratingModel);
    }

    public function testGetGameRankingUnknownGame(): void
    {
        $this->get($this->apiUrl('GetGameRating', ['i' => 999999]))
            ->assertSuccessful()
            ->assertJson([
                'GameID' => 999999,
                'Ratings' => [
                    'Game' => 0.0,
                    'Achievements' => 0.0,
                    'GameNumVotes' => 0,
                    'AchievementsNumVotes' => 0,
                ],
            ]);
    }

    public function testGetGameRanking(): void
    {
        /** @var System $system */
        $system = System::factory()->create();
        /** @var Game $game */
        $game = Game::factory()->create(['ConsoleID' => $system->ID]);

        $ratings = [5, 3, 5, 4, 4, 5, 1, 5, 3, 5, 4, 5, 3, 5];
        $numRatings = count($ratings);
        $avgRating = array_sum($ratings) / $numRatings;

        $achRatings = [5, 4, 3, 4, 4, 5, 4];
        $numAchRatings = 0;
        $avgAchRating = 0;

        foreach ($ratings as $rating) {
            $user = User::factory()->create();
            $this->addGameRating($game, $user, $rating);

            if ($rating === 5) {
                $achRating = $achRatings[$numAchRatings++];
                $this->addAchievementRating($game, $user, $achRating);
                $avgAchRating += $achRating;
            }
        }
        $avgAchRating /= $numAchRatings;

        $this->get($this->apiUrl('GetGameRating', ['i' => $game->ID]))
            ->assertSuccessful()
            ->assertJson([
                'GameID' => $game->ID,
                'Ratings' => [
                    'Game' => $avgRating,
                    'Achievements' => $avgAchRating,
                    'GameNumVotes' => $numRatings,
                    'AchievementsNumVotes' => $numAchRatings,
                ],
            ]);
    }
}
