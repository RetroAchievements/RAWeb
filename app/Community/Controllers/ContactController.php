<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class ContactController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.contact');
    }
}
