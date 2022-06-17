<?php

declare(strict_types=1);

namespace App\Site\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotificationIcon extends Component
{
    public function render(): View
    {
        return view('components.notification.icon')
            ->with('count', 0);
    }
}
