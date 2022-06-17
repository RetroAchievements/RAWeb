<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class PromptLayout extends Component
{
    public function __construct(
        public ?string $pageTitle = null,
        public bool $withTop = true,
    ) {
    }

    public function render(): View
    {
        return view('layouts.prompt');
    }
}
