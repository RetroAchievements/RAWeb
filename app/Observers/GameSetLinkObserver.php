<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\GameSet;
use App\Models\GameSetLink;
use App\Platform\Enums\GameSetType;
use Illuminate\Support\Facades\Cache;

class GameSetLinkObserver
{
    public function created(GameSetLink $link): void
    {
        $this->clearRelatedBreadcrumbCaches($link);
    }

    public function deleted(GameSetLink $link): void
    {
        $this->clearRelatedBreadcrumbCaches($link);
    }

    /**
     * Clear breadcrumb caches when a link between hubs changes.
     */
    private function clearRelatedBreadcrumbCaches(GameSetLink $link): void
    {
        // Load the related game sets.
        $parent = GameSet::find($link->parent_game_set_id);
        $child = GameSet::find($link->child_game_set_id);

        if (
            !$parent
            || !$child
            || $parent->type !== GameSetType::Hub
            || $child->type !== GameSetType::Hub
        ) {
            return;
        }

        // Clear the cache for both hubs.
        Cache::forget("hub_breadcrumbs:{$parent->id}");
        Cache::forget("hub_breadcrumbs:{$child->id}");

        // Clear the caches for all child hubs of the child hub (they're affected too).
        $child->children()
            ->whereType(GameSetType::Hub)
            ->each(function (GameSet $grandchild) {
                Cache::forget("hub_breadcrumbs:{$grandchild->id}");
            });
    }
}
