<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Header extends Component
{
    public function __construct(
        public ?string $class = null,
    ) {
    }

    public function render(): View
    {
        return view('layouts.components.header');
    }
}
