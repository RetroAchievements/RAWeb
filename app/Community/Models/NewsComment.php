<?php

declare(strict_types=1);

namespace App\Community\Models;

class NewsComment extends Comment
{
    public function getEditLinkAttribute(): string
    {
        return route('news.comment.edit', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('news.comment.show', $this);
    }
}
