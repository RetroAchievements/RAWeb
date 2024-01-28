<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SiteInfoWidget extends Widget
{
    protected static ?int $sort = -2;

    protected static bool $isLazy = false;

    protected static string $view = 'filament.widgets.site-info-widget';
}
