<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\UserGameListType;
use App\Enums\Permissions;
use App\Http\Controller;
use App\Models\UserGameListEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

// TODO use a policy for the 403 returns

class GameDevInterestController extends Controller
{
    public function __invoke(Request $request): View
    {
        if (!$request->user()) {
            abort(401);
        }

        $permissions = $request->user()->getAttribute('Permissions');
        if ($permissions < Permissions::JuniorDeveloper) {
            abort(403);
        }

        $gameId = (int) $request->route('game');
        $gameData = getGameData($gameId);
        if ($gameData === null) {
            abort(404);
        }

        if ($permissions < Permissions::Moderator && !hasSetClaimed($request->user(), $gameId, true)) {
            abort(403);
        }

        $listUsers = UserGameListEntry::where('type', UserGameListType::Develop)
            ->where('GameID', $gameId)
            ->join('UserAccounts', 'UserAccounts.ID', '=', 'SetRequest.user_id')
            ->orderBy('UserAccounts.User')
            ->pluck('UserAccounts.User');

        return view('pages.game.[game].dev-interest', [
            'gameId' => $gameData['ID'],
            'gameTitle' => $gameData['Title'],
            'consoleId' => $gameData['ConsoleID'],
            'consoleName' => $gameData['ConsoleName'],
            'imageIcon' => media_asset($gameData['ImageIcon']),
            'users' => $listUsers,
        ]);
    }
}
