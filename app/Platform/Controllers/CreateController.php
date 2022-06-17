<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class CreateController extends Controller
{
    public function __invoke(): View
    {
        return view('create');
    }
}
