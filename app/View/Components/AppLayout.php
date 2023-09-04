<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

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
    ) {
    }

    public function render(): View
    {
        return view('layouts.app');
    }
}
