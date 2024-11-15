<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use App\Platform\Data\LeaderboardData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('LeaderboardCommentsPageProps<TItems = App.Community.Data.Comment>')]
class LeaderboardCommentsPagePropsData extends Data
{
    public function __construct(
        public LeaderboardData $leaderboard,
        public PaginatedData $paginatedComments,
        public bool $isSubscribed,
        public bool $canComment,
    ) {
    }
}
