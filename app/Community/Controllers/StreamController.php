<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class StreamController extends Controller
{
    public function index(): View
    {
        return view('stream.index');
    }
}
