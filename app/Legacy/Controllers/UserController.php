<?php

declare(strict_types=1);

namespace App\Legacy\Controllers;

use App\Http\Controller;
use App\Legacy\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Jenssegers\Optimus\Optimus;

class UserController extends Controller
{
    public function permalink(Optimus $optimus, int $hashId): Redirector|Application|RedirectResponse
    {
        $userId = $optimus->decode($hashId);
        $user = User::findOrFail($userId);

        return redirect(route('user.show', $user));
    }
}
