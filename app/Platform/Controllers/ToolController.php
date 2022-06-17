<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class ToolController extends Controller
{
    public function index(): View
    {
        return view('tool.index');
    }
}
