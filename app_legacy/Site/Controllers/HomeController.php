<?php

declare(strict_types=1);

namespace LegacyApp\Site\Controllers;

use Illuminate\Contracts\View\View;
use LegacyApp\Http\Controller;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home');
    }
}
