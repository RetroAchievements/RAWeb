<?php

namespace App\Livewire\Game;

use App\Models\Game;
use App\Models\User;
use App\Platform\Services\GameUserAchievementsGridService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UserAchievementsGrid extends Component
{
    public ?int $achievementCount = null;

    #[Locked]
    public ?int $gameId = null;

    #[Locked]
    public ?string $targetUsername = null;

    public array $gameAchievementsWithProgress = [];

    public bool $isLoading = true;

    public function loadContent(): void
    {
        $game = Game::find($this->gameId);
        $user = User::whereName($this->targetUsername)->first();

        $service = new GameUserAchievementsGridService();
        $this->gameAchievementsWithProgress = $service->getGameAchievementsWithUserProgress(
            $game,
            $user,
        );
    }

    public function render(): View
    {
        return view('livewire.game.user-achievements-grid');
    }
}
