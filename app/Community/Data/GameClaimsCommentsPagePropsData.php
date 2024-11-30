<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use App\Platform\Data\GameData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameClaimsCommentsPageProps<TItems = App.Community.Data.Comment>')]
class GameClaimsCommentsPagePropsData extends Data
{
    public function __construct(
        public GameData $game,
        public PaginatedData $paginatedComments,
        public bool $isSubscribed,
        public bool $canComment,
    ) {
    }
}
