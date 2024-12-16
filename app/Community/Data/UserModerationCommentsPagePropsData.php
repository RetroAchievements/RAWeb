<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\PaginatedData;
use App\Data\UserData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('UserModerationCommentsPageProps<TItems = App.Community.Data.Comment>')]
class UserModerationCommentsPagePropsData extends Data
{
    public function __construct(
        public UserData $targetUser,
        public PaginatedData $paginatedComments,
        public bool $isSubscribed,
        public bool $canComment,
    ) {
    }
}
