<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class NotificationsController extends Controller
{
    public function index(): View
    {
        return view('inbox.notifications');
    }
}
