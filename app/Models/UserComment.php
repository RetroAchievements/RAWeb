<?php

declare(strict_types=1);

namespace App\Models;

class UserComment extends Comment
{
    public function getEditLinkAttribute(): string
    {
        return route('user.comment.edit', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('user.comment.show', $this);
    }
}
