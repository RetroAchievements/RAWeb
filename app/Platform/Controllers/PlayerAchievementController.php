<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PlayerAchievementController extends Controller
{
    public function index(User $user): View
    {
        return view('player.achievement.index')
            ->with('user', $user);
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function show(User $user): void
    {
    }

    public function edit(User $user): void
    {
    }

    public function update(Request $request, User $user): void
    {
    }

    public function destroy(User $user): void
    {
    }
}
