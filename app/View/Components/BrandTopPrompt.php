<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BrandTopPrompt extends Component
{
    public function render(): View
    {
        return view('layouts.components.brand-top-prompt');
    }
}
