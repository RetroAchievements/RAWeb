<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BrandTop extends Component
{
    public function render(): View
    {
        return view('layouts.components.brand-top');
    }
}
