<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\Game;
use Illuminate\Contracts\View\View;

class GamePlayerController extends Controller
{
    protected function resourceName(): string
    {
        return 'game.player';
    }

    public function index(Game $game): View
    {
        return view('game.player.index')
            ->with('game', $game);
    }
}
