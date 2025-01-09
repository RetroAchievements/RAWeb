<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildDeveloperFeedDataAction;
use App\Http\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AchievementAuthorController extends Controller
{
    // TODO developerstats.php?
    public function index(): void
    {
    }

    // TODO individualdevstats.php?
    public function show(): void
    {
    }

    public function create(): void
    {
    }

    public function edit(): void
    {
    }

    public function update(): void
    {
    }

    public function destroy(): void
    {
    }

    public function feed(Request $request, User $user): InertiaResponse
    {
        abort_if($user->ContribCount === 0, 404);

        $this->authorize('viewDeveloperFeed', $user);

        $props = (new BuildDeveloperFeedDataAction())->execute($user);

        return Inertia::render('user/[user]/developer/feed', $props);
    }
}
