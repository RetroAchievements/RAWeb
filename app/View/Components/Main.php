<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Main extends Component
{
    public bool $fluid = false;
    public string $sidebarPosition = 'right';

    public function render(): View
    {
        return view('layouts.components.main')
            ->with('fluid', $this->fluid)
            ->with('sidebarPosition', $this->sidebarPosition);
    }
}
