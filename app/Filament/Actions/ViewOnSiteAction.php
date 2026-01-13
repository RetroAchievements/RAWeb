<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Platform\Contracts\HasPermalink;
use Filament\Actions\Action;

class ViewOnSiteAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('View on Site')
            ->icon('heroicon-m-arrow-top-right-on-square')
            ->color('gray')
            ->url(fn (HasPermalink $record): string => $record->getPermalinkAttribute())
            ->openUrlInNewTab();
    }
}
