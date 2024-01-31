<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use Illuminate\Contracts\View\View;

class RssController extends Controller
{
    public function index(): View
    {
        return view('rss');
    }

    public function show(string $resource): void
    {
        /*
         * TODO: move complete rss code over here
         */
        abort(501);
    }
}
