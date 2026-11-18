<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\UserRelation;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

class UserRelationObserver
{
    public function saved(UserRelation $userRelation): void
    {
        $this->forgetMaskedForumAuthorIds($userRelation);
    }

    public function deleted(UserRelation $userRelation): void
    {
        $this->forgetMaskedForumAuthorIds($userRelation);
    }

    private function forgetMaskedForumAuthorIds(UserRelation $userRelation): void
    {
        Cache::forget(CacheKey::buildUserMaskedForumAuthorIdsCacheKey($userRelation->user_id));
    }
}
