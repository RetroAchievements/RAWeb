<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Main extends Component
{
    public function __construct(
        public bool $fluid = false,
        public string $sidebarPosition = 'right',
        public ?string $class = '',
        public bool $withoutWrappers = false,
    ) {
    }

    public function render(): View
    {
        return view('layouts.components.main');
    }
}
