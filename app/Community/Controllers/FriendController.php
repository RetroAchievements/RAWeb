<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Http\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request, ?User $user = null): View|RedirectResponse
    {
        /*
         * forward alias
         */
        if (!$user) {
            return redirect(route('user.friend.index', $request->user()));
        }

        return view('friend.index')
            ->with('user', $user)
            ->with('relation', 'friend');
    }

    public function create(): void
    {
    }

    public function store(Request $request): void
    {
    }

    public function show(User $friend): void
    {
    }

    public function edit(User $friend): void
    {
    }

    public function update(Request $request, User $friend): void
    {
    }

    public function destroy(User $friend): void
    {
    }
}
