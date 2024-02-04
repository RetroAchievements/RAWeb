<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Components\Concerns\DeferLoading;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PlayersActive extends Component
{
    use DeferLoading;

    public function render(): View
    {
        $players = User::currentlyOnline()->get();
        $playersOnline = User::currentlyOnline()->count();

        return view('components.player.active')
            ->with('players', $players)
            ->with('playersOnline', $playersOnline);
    }
}
