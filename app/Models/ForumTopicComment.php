<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\ForumTopicCommentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class ForumTopicComment extends BaseModel
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    // TODO rename ForumTopicComment table to forum_topic_comments
    // TODO rename ID column to id, remove getIdAttribute()
    // TODO rename ForumTopicID to forum_topic_id, remove getForumTopicIdAttribute()
    // TODO rename Payload column to body, remove getBodyAttribute()
    // TODO rename DateCreated to created_at
    // TODO rename DateModified to updated_at
    // TODO drop Author -> derived
    // TODO drop Authorised, migrate to authorized_at
    protected $table = 'ForumTopicComment';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'DateCreated';
    public const UPDATED_AT = 'DateModified';

    protected $fillable = [
        'ForumTopicID',
        'Payload',
        'author_id',
        'Authorised',
        'authorized_at',
    ];

    protected $casts = [
        'ManuallyVerified' => 'boolean',
    ];

    protected static function newFactory(): ForumTopicCommentFactory
    {
        return ForumTopicCommentFactory::new();
    }

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

    // == accessors

    public function getBodyAttribute(): string
    {
        return $this->attributes['Payload'];
    }

    public function getEditLinkAttribute(): string
    {
        return route('forum-topic-comment.edit', $this);
    }

    public function getIsAuthorizedAttribute(): bool
    {
        return $this->authorized_at?->isPast() || (bool) $this->attributes['Authorised'];
    }

    public function getPermalinkAttribute(): string
    {
        return route('forum-topic-comment.show', $this);
    }

    // TODO remove after rename
    public function getForumTopicIdAttribute(): int
    {
        return $this->attributes['ForumTopicID'];
    }

    // TODO remove after rename
    public function getIdAttribute(): int
    {
        return $this->attributes['ID'];
    }

    // == relations

    /**
     * @return BelongsTo<ForumTopic, ForumTopicComment>
     */
    public function forumTopic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'ForumTopicID');
    }

    /**
     * @return BelongsTo<User, ForumTopicComment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }

    // == scopes

    /**
     * @param Builder<ForumTopicComment> $query
     * @return Builder<ForumTopicComment>
     */
    public function scopeAuthorized(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->where('Authorised', 1)
                ->orWhereNotNull('authorized_at');
        });
    }

    /**
     * @param Builder<ForumTopicComment> $query
     * @return Builder<ForumTopicComment>
     */
    public function scopeUnauthorized(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->where('Authorised', 0)
                ->orWhereNull('authorized_at');
        });
    }
}
