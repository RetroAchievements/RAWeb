<?php

declare(strict_types=1);

namespace App\Platform\Components;

use App\Site\Components\Concerns\DeferLoading;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PlayersActive extends Component
{
    use DeferLoading;

    public function render(): View
    {
        $players = \App\Site\Models\User::currentlyOnline()->get();
        $playersOnline = \App\Site\Models\User::currentlyOnline()->count();

        return view('components.player.active')
            ->with('players', $players)
            ->with('playersOnline', $playersOnline);
    }
}
