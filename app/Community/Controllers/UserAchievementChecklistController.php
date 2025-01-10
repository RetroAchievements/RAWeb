<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildAchievementChecklistAction;
use App\Community\Data\AchievementChecklistPagePropsData;
use App\Data\UserData;
use App\Http\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserAchievementChecklistController extends Controller
{
    public function index(Request $request, User $user): InertiaResponse
    {
        $this->authorize('view', $user);

        $list = $request->get('list');

        $groups = (new BuildAchievementChecklistAction())->execute($list, $user);

        $props = new AchievementChecklistPagePropsData(
            UserData::fromUser($user),
            $groups,
        );

        return Inertia::render('user/[user]/achievement-checklist', $props);
    }
}
