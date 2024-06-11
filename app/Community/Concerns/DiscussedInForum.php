<?php

declare(strict_types=1);

namespace App\Community\Concerns;

use App\Models\ForumTopic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait DiscussedInForum
{
    public static function bootDiscussedInForum(): void
    {
    }

    // @phpstan-ignore-next-line
    public function forumTopic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'ForumTopicID', 'ID');
    }
}
