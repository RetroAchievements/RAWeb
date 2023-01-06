<?php

declare(strict_types=1);

namespace LegacyApp\Site\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Jenssegers\Optimus\Optimus;
use LegacyApp\Http\Controller;
use LegacyApp\Site\Models\User;

class UserController extends Controller
{
    public function permalink(Optimus $optimus, int $hashId): Redirector|Application|RedirectResponse
    {
        $userId = $optimus->decode($hashId);
        $user = User::findOrFail($userId);

        return redirect(route('user.show', $user));
    }
}
