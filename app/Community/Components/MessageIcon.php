<?php

declare(strict_types=1);

namespace App\Community\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class MessageIcon extends Component
{
    public function render(): View
    {
        return view('components.message.icon')
            ->with('count', 0);
    }
}
