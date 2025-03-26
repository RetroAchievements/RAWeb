<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\Event;
use App\Models\User;
use App\Platform\Actions\BuildEventShowPagePropsAction;
use App\Platform\Actions\LoadEventWithRelationsAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EventController extends Controller
{
    public function show(
        Request $request,
        Event $event,
        LoadEventWithRelationsAction $loadEventWithRelationsAction,
        BuildEventShowPagePropsAction $buildEventShowPagePropsAction,
    ): InertiaResponse {
        $this->authorize('view', $event);

        /** @var ?User $user */
        $user = $request->user();

        $event = $loadEventWithRelationsAction->execute($event, $user);
        $props = $buildEventShowPagePropsAction->execute($event, $user);

        return Inertia::render('event/[event]', $props);
    }
}
