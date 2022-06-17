<?php

declare(strict_types=1);

namespace App\Community\Models;

use Illuminate\Database\Eloquent\Model;

class ForumTopicComment extends Comment
{
    public function getEditLinkAttribute(): string
    {
        return route('forum-topic.comment.edit', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('forum-topic.comment.show', $this);
    }

    public function getTopicAttribute(): Model
    {
        return $this->commentable;
    }
}
