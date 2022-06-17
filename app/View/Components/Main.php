<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

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
