<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Models\Game;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManageHashesController extends Controller
{
    public function __invoke(Request $request): View
    {
        // TODO: Refactor this to use middleware once the permissions matrix is in place.
        $me = Auth::user() ?? null;
        if (!$this->getCanViewPage($me)) {
            abort(401);
        }

        $targetGameId = $request->route()->parameters['game'];
        $foundGame = Game::with(['system', 'hashes'])->find($targetGameId);
        // TODO: Use middleware.
        if (!$foundGame) {
            abort(404);
        }

        return view('platform.manage-hashes-page', [
            'game' => $foundGame,
            'me' => $me,
        ]);
    }

    // TODO: Refactor this to use middleware once the permissions matrix is in place.
    private function getCanViewPage(?User $me): bool
    {
        return isset($me) && $me->Permissions >= Permissions::Developer;
    }
}
