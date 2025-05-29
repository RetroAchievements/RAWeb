<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Data\PaginatedData;
use App\Data\UserData;
use App\Http\Controller;
use App\Models\Event;
use App\Platform\Data\AwardEarnerData;
use App\Platform\Data\EventData;
use App\Platform\Data\EventAwardData;
use App\Platform\Data\EventAwardEarnersPagePropsData;
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

        $eventAward = $event->awards()->where('tier_index', $tier)->first();
        if (!$eventAward) {
            abort(404); 
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

        $paginatedUsers = $awardEarnersService->allEarners()
            ->paginate($perPage);
        if ($tier !== 0) {
            $paginatedUsers->appends(['tier' => $tier]);
        }

        $items = [];
        $rank = 0;
        $rankScore = -1;
        $firstRank = 0;
        $nextRank = 0;
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
