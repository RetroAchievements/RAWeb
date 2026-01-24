<?php

declare(strict_types=1);

namespace App\Api\V2\LeaderboardEntries;

use App\Api\V2\BaseJsonApiResource;
use App\Models\LeaderboardEntry;
use App\Platform\Enums\ValueFormat;
use Illuminate\Http\Request;

/**
 * @property LeaderboardEntry $resource
 */
class LeaderboardEntryResource extends BaseJsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        $leaderboard = $this->resource->leaderboard;

        return [
            'score' => $this->resource->score,
            'formattedScore' => ValueFormat::format($this->resource->score, $leaderboard->format),
            'rank' => $this->resource->rank,

            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        return [
            'user' => $this->relation('user')->withoutLinks(),
            'leaderboard' => $this->relation('leaderboard')->withoutLinks(),
        ];
    }

}
