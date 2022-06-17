<?php

declare(strict_types=1);

namespace App\Site\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class SearchController extends Controller
{
    public function index(): View
    {
        return view('search');
    }
}
