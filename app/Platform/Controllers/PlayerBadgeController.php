<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\User;
use App\Platform\Models\PlayerBadge;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PlayerBadgeController extends Controller
{
    public function index(User $player): View
    {
        $this->authorize('viewAny', [PlayerBadge::class, $player]);

        return view('player.badge.index')
            ->with('user', $player);
    }

    public function create(): void
    {
        $this->authorize('create', PlayerBadge::class);
    }

    public function store(Request $request): void
    {
        $this->authorize('create', PlayerBadge::class);
    }

    public function show(PlayerBadge $playerBadge): void
    {
        $this->authorize('view', $playerBadge);
    }

    public function edit(PlayerBadge $playerBadge): void
    {
        $this->authorize('update', $playerBadge);
    }

    public function update(Request $request, PlayerBadge $playerBadge): void
    {
        $this->authorize('update', $playerBadge);
    }

    public function destroy(PlayerBadge $playerBadge): void
    {
        $this->authorize('delete', $playerBadge);
    }
}
