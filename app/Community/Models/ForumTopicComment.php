<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumTopicComment extends BaseModel
{
    use SoftDeletes;

    // TODO rename ForumTopicComment table to forum_topic_comments
    // TODO rename ID column to id
    // TODO rename ForumTopicID to forum_topic_id
    // TODO rename Payload column to body
    // TODO rename AuthorID to author_id
    // TODO rename DateCreated to created_at
    // TODO rename DateModified to updated_at
    // TODO drop Author -> derived
    // TODO drop Authorised, migrate to authorized_at
    protected $table = 'ForumTopicComment';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'DateCreated';
    public const UPDATED_AT = 'DateModified';

    public function getEditLinkAttribute(): string
    {
        return route('forum-topic-comment.edit', $this);
    }

    public function getPermalinkAttribute(): string
    {
        return route('forum-topic-comment.show', $this);
    }

    // == relations

    /**
     * @return BelongsTo<User, ForumTopicComment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'AuthorID');
    }
}
