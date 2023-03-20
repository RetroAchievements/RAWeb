<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Content extends Component
{
    public function render(): View
    {
        return view('layouts.components.content');
    }
}
