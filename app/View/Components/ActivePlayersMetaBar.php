<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ActivePlayersMetaBar extends Component
{
    public $activePlayersCount = 0;
    public $hasError = false;

    public function __construct(int $activePlayersCount, bool $hasError)
    {
        $this->activePlayersCount = $activePlayersCount;
        $this->hasError = $hasError;
    }

    public function render()
    {
        return view('components.active-players-meta-bar');
    }
}
