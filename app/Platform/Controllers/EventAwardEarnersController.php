<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Data\PaginatedData;
use App\Data\UserData;
use App\Http\Controller;
use App\Models\Event;
use App\Models\EventAward;
use App\Platform\Data\AwardEarnerData;
use App\Platform\Data\EventAwardData;
use App\Platform\Data\EventAwardEarnersPagePropsData;
use App\Platform\Data\EventData;
use App\Platform\Services\AwardEarnersService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EventAwardEarnersController extends Controller
{
    public function index(
        Event $event,
        AwardEarnersService $awardEarnersService,
    ): InertiaResponse|RedirectResponse {
        $this->authorize('viewAny', [$event]);

        $perPage = 50;
        $currentPage = (int) request()->input('page', 1);
        $tier = (int) request()->input('tier', 0);

        if ($tier === 0) {
            if ($event->awards()->count() !== 0) {
                abort(404);
            }
            $eventAward = new EventAward([
                'event_id' => $event->id,
                'tier_index' => 0,
                'label' => $event->title,
                'points_required' => $event->publishedAchievements->count(),
                'image_asset_path' => $event->image_asset_path,
            ]);
            $eventAward->id = 0;
        } else {
            $eventAward = $event->awards()->where('tier_index', $tier)->first();
            if (!$eventAward) {
                abort(404);
            }
        }

        $awardEarnersService->initialize(AwardType::Event, $event->id, $tier);

        // Get total entries to calculate the last page.
        $totalEntries = $awardEarnersService->numEarners();
        $lastPage = (int) ceil($totalEntries / $perPage);

        // If the current page exceeds the last page, redirect to the last page.
        if ($currentPage !== 1 && $currentPage > $lastPage) {
            return redirect()->route('event.award-earners.index', [
                'event' => $event->id,
                'tier' => $tier,
                'page' => $lastPage,
            ]);
        }

        $paginatedUsers = $awardEarnersService->allEarners()->paginate($perPage);
        if ($tier !== 0) {
            $paginatedUsers->appends(['tier' => $tier]);
        }

        $items = [];
        foreach ($paginatedUsers->items() as $playerBadge) {
            $items[] = new AwardEarnerData(
                user: UserData::fromUser($playerBadge->user),
                dateEarned: $playerBadge->AwardDate,
            );
        }

        $props = new EventAwardEarnersPagePropsData(
            EventData::fromEvent($event)->include('legacyGame'),
            EventAwardData::fromEventAward($eventAward),
            PaginatedData::fromLengthAwarePaginator(
                $paginatedUsers,
                total: $totalEntries,
                items: $items,
            ),
        );

        return Inertia::render('event/[event]/award-earners', $props);
    }
}
