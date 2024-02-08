<?php

declare(strict_types=1);

namespace App\Models;

class GameComment extends Comment
{
    public function getEditLinkAttribute(): string
    {
        return route('game.comment.edit', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('game.comment.show', $this);
    }
}
