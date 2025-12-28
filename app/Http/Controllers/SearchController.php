<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use App\Http\Data\SearchPagePropsData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SearchController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        // Just hydrate everything client-side, it's probably not a big deal.

        return Inertia::render('search', new SearchPagePropsData(
            initialQuery: $request->query('query', ''),
            initialScope: $request->query('scope', 'all'),
            initialPage: (int) $request->query('page', 1),
        ));
    }
}
