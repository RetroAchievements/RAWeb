<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Models\Leaderboard;
use LaravelJsonApi\Core\Pagination\Page;
use LaravelJsonApi\Core\Responses\RelatedResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;

class LeaderboardController extends JsonApiController
{
    use Actions\FetchMany;
    use Actions\FetchOne;

    /**
     * Efficiently calculate ranks for leaderboard entries after they're fetched.
     * Instead of running a COUNT subquery per row, we calculate the rank of the
     * first entry and derive subsequent ranks from their position in the result set.
     *
     * @see GetRankedLeaderboardEntriesAction.php
     */
    protected function readRelatedEntries(
        Leaderboard $leaderboard,
        Page $data,
        ResourceQuery $request,
    ): RelatedResponse {
        $pageParams = $request->page();
        $perPage = $pageParams['size'] ?? 50;
        $currentPage = $pageParams['number'] ?? 1;

        $index = (($currentPage - 1) * $perPage) + 1;
        $previousScore = null;
        $rank = 0;

        foreach ($data as $entry) {
            if ($entry->score !== $previousScore) {
                // When the score changes, calculate the new rank. For the first entry,
                // query the actual rank. For subsequent entries, use the current index.
                $rank = $previousScore === null
                    ? $leaderboard->getRank($entry->score)
                    : $index;

                $previousScore = $entry->score;
            }

            $entry->rank = $rank;
            $index++;
        }

        return RelatedResponse::make($leaderboard, 'entries', $data)
            ->withQueryParameters($request);
    }
}
