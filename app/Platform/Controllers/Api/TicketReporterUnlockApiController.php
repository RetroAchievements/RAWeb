<?php

declare(strict_types=1);

namespace App\Platform\Controllers\Api;

use App\Http\Controller;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\Ticket;
use App\Platform\Actions\UnlockPlayerAchievementAction;
use App\Platform\Requests\StoreTicketReporterUnlockRequest;
use Illuminate\Http\Response;

class TicketReporterUnlockApiController extends Controller
{
    public function store(
        StoreTicketReporterUnlockRequest $unlockRequest,
        Ticket $ticket,
        UnlockPlayerAchievementAction $unlockAchievement,
    ): Response {
        $this->authorize('manuallyAward', PlayerAchievement::class);

        $reportedAchievement = $ticket->ticketable;
        abort_if(
            !$reportedAchievement instanceof Achievement || $ticket->reporter === null,
            404,
            'This ticket has no reporter or achievement to award.',
        );

        $wantsHardcore = $unlockRequest->boolean('isHardcore');
        abort_if($wantsHardcore && !$ticket->hardcore, 422, 'Cannot award a hardcore unlock for a casual report.');

        $unlockAchievement->execute(
            $ticket->reporter,
            $reportedAchievement,
            $wantsHardcore,
            unlockedBy: $unlockRequest->user(),
        );

        return response()->noContent(); // 204
    }
}
