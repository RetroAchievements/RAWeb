<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Community\Contracts\HasComments;
use App\Community\Events\ForumTopicCreated;
use App\Support\Database\Eloquent\BaseModel;
use App\Support\Shortcode\HasShortcodeFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ForumTopic extends BaseModel implements HasComments
{
    use HasShortcodeFields;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'body',
    ];

    protected $with = [
        'user',
        'forum',
    ];

    protected $dispatchesEvents = [
        // 'created' => ForumTopicCreated::class,
    ];

    protected $observables = [];

    /**
     * @see HasShortcodeFields
     */
    protected array $shortcodeFields = [
        'body',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'title',
            'body',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // return $this->isPublished();
        return true;
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('forum-topic.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('forum-topic.show', $this);
    }

    public function getEditLinkAttribute(): string
    {
        return route('forum-topic.edit', $this);
    }

    public function getSlugAttribute(): string
    {
        return ($this->forum->category->title ? '-' . Str::slug($this->forum->category->title) : '')
            . ($this->forum->title ? '-' . Str::slug($this->forum->title) : '')
            . ($this->title ? '-' . Str::slug($this->title) : '');
    }

    // == mutators

    // == relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Site\Models\User::class);
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(ForumTopicComment::class, 'commentable')->with('user');
    }

    public function latestComment(): MorphOne
    {
        return $this->morphOne(Comment::class, 'commentable')->latestOfMany();
    }

    // == scopes

    /**
     * Order by latest comment dynamic relationship
     */
    public function scopeOrderByLatestActivity(Builder $query, string $direction = 'asc'): void
    {
        /*
         * unfortunately there's no easy way to create a coalesce with the query builder to make it work with postgres
         * mysql is ok with ordering coalescing with an alias in it
         * otherwise the above sub-select would've been enough
         */
        $query->selectRaw(
            'coalesce(
            (
                SELECT
                    "created_at"
                FROM
                    "comments"
                WHERE
                    "comments"."commentable_type" = \'forum-topic\'
                    AND "comments"."commentable_id" = "forum_topics"."id"
                    AND "deleted_at" IS NULL
                ORDER BY
                    "created_at" DESC
                LIMIT 1)
                , created_at
            ) as last_activity_at'
        );

        $query->orderByRaw('last_activity_at ' . $direction);
    }
}
