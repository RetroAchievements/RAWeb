<?php

declare(strict_types=1);

namespace App\Models;

class AchievementComment extends Comment
{
    public function getEditLinkAttribute(): string
    {
        return route('achievement.comment.edit', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('achievement.comment.show', $this);
    }
}
