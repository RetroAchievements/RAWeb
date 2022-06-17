<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Models\GameHash;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GameHashController extends Controller
{
    protected function resourceName(): string
    {
        return 'game-hash';
    }

    public function index(): View
    {
        $this->authorize('viewAny', $this->resourceClass());

        return view('resource.index')
            ->with('resource', $this->resourceName());
    }

    public function show(GameHash $gameHash): void
    {
        dump($gameHash->toArray());
    }

    public function edit(GameHash $gameHash): void
    {
    }

    public function update(Request $request, GameHash $gameHash): void
    {
    }

    public function destroy(GameHash $gameHash): void
    {
    }
}
