<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildGameChecklistAction;
use App\Community\Data\GameChecklistPagePropsData;
use App\Data\UserData;
use App\Http\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserGameChecklistController extends Controller
{
    public function index(Request $request, User $user): InertiaResponse
    {
        $this->authorize('view', $user);

        $list = $request->get('list');

        $groups = (new BuildGameChecklistAction())->execute($list, $user);

        $props = new GameChecklistPagePropsData(
            UserData::fromUser($user),
            $groups,
        );

        return Inertia::render('user/[user]/game-checklist', $props);
    }
}
