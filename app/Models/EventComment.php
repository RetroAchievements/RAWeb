<?php

declare(strict_types=1);

namespace App\Models;

class EventComment extends Comment
{
    public function getEditLinkAttribute(): string
    {
        return route('event.comment.edit', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('event.comment.show', $this);
    }
}
