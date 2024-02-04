<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        /*
         * TODO: redirect to either notifications or messages depending on which one has new content
         */

        return redirect(route('notification.index'));
    }
}
