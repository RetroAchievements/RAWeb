<?php

declare(strict_types=1);

namespace App\Platform\Controllers\Api;

use App\Community\Enums\AwardType;
use App\Http\Controller;
use App\Models\EventAward;
use App\Models\User;
use App\Platform\Requests\UpdateEventAwardTierPreferenceRequest;
use Illuminate\Http\JsonResponse;

class UserEventAwardTierPreferenceApiController extends Controller
{
    public function update(UpdateEventAwardTierPreferenceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();
        $eventId = $validated['eventId'];
        $tierIndex = $validated['tierIndex'];

        $playerBadge = $user->playerBadges()
            ->where('award_type', AwardType::Event)
            ->where('award_key', $eventId)
            ->first();

        abort_if(!$playerBadge, 404, 'No award found for this event.');

        // The user cannot select a tier higher than what they've earned.
        abort_if(
            $tierIndex !== null && $tierIndex > $playerBadge->award_tier,
            422,
            'Cannot select a tier higher than your earned tier.'
        );

        // The selected tier must correspond to an actual event award row.
        abort_if(
            $tierIndex !== null
                && !EventAward::where('event_id', $eventId)->where('tier_index', $tierIndex)->exists(),
            422,
            'Invalid tier.',
        );

        $playerBadge->display_award_tier = $tierIndex;
        $playerBadge->save();

        return response()->json(['success' => true]);
    }
}
