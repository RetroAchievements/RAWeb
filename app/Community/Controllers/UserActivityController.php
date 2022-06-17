<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use Illuminate\Contracts\View\View;

class UserActivityController extends \App\Http\Controller
{
    public function index(): View
    {
        return view('user.activity.index');
    }
}
