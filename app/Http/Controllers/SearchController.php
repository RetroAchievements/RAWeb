<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use App\Http\Data\SearchPagePropsData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SearchController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        // Just hydrate everything client-side, it's probably not a big deal.

        $query = $request->query('query', '');

        // Prefetch a quick "did you mean" username suggestion so the search page can
        // show it immediately without waiting on the client-side hydration round trip.
        $suggestion = null;
        if ($query !== '') {
            $rows = DB::select(
                "SELECT User FROM UserAccounts WHERE User LIKE '%{$query}%' AND Deleted IS NULL ORDER BY RAPoints DESC LIMIT 1"
            );

            $suggestion = $rows[0]->User ?? null;
        }

        return Inertia::render('search', new SearchPagePropsData(
            initialQuery: $query,
            initialScope: $request->query('scope', 'all'),
            initialPage: (int) $request->query('page', 1),
            suggestedUsername: $suggestion,
        ));
    }
}
