<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\Badge;
use App\Models\Game;
use Illuminate\Contracts\View\View;

class GameBadgeController extends Controller
{
    public function index(Game $game): View
    {
        $this->authorize('view', $game);
        $this->authorize('viewAny', Badge::class);

        return view('server.game.badge.index')
            ->with('game', $game);
    }
}
