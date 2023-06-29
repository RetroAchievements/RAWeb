<?php

declare(strict_types=1);

namespace App\Site\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home');
    }
}
