<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ActivePlayersList extends Component
{
    public $activePlayers;

    public function __construct(array $activePlayers)
    {
        $this->activePlayers = $activePlayers;
    }

    public function render()
    {
        return view('components.active-players-list');
    }
}
