<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Platform\Actions\ComputeGameSortTitleAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucwords(fake()->words(2, true));
        $sortTitle = (new ComputeGameSortTitleAction())->execute($title);

        return [
            'Title' => $title,
            'sort_title' => $sortTitle,
            'ConsoleID' => 0,
            'ImageIcon' => '/Images/000001.png',
        ];
    }
}
