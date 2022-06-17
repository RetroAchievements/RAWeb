<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public function __construct(
        public ?string $pageTitle = null,
        public ?string $pageDescription = null,
        public ?string $pageImage = null,
        public ?string $permalink = null,
        public ?string $pageType = null,
        public ?string $canonicalUrl = null,
        public string $sidebarPosition = 'right',
        public bool $wide = false,
    ) {
    }

    public function render(): View
    {
        return view('layouts.app');
    }
}
