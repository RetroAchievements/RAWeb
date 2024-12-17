<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\GameSet;
use App\Platform\Enums\GameSetType;
use Illuminate\Support\Facades\Cache;

class GameSetObserver
{
    public function updated(GameSet $gameSet): void
    {
        $this->clearRelatedBreadcrumbCaches($gameSet);
    }

    public function deleted(GameSet $gameSet): void
    {
        $this->clearRelatedBreadcrumbCaches($gameSet);
    }

    /**
     * Clear breadcrumb caches for a hub and all related hubs.
     */
    private function clearRelatedBreadcrumbCaches(GameSet $gameSet): void
    {
        if ($gameSet->type !== GameSetType::Hub) {
            return;
        }

        // Clear the cache for this hub.
        Cache::forget("hub_breadcrumbs:{$gameSet->id}");

        // Clear caches for all child hubs (they include this hub in their breadcrumbs).
        $gameSet->children()
            ->whereType(GameSetType::Hub)
            ->each(function (GameSet $child) {
                Cache::forget("hub_breadcrumbs:{$child->id}");
            });
    }
}
