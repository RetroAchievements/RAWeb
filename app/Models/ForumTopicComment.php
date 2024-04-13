<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class ForumTopicComment extends BaseModel
{
    use Searchable;
    use SoftDeletes;

    // TODO rename ForumTopicComment table to forum_topic_comments
    // TODO rename ID column to id
    // TODO rename ForumTopicID to forum_topic_id
    // TODO rename Payload column to body
    // TODO rename DateCreated to created_at
    // TODO rename DateModified to updated_at
    // TODO drop Author -> derived
    // TODO drop Authorised, migrate to authorized_at
    protected $table = 'ForumTopicComment';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'DateCreated';
    public const UPDATED_AT = 'DateModified';

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'ID',
            'Payload',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO return true;
        return false;
    }

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
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }
}
