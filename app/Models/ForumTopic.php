<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\ForumTopicFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ForumTopic extends BaseModel
{
    /** @use HasFactory<ForumTopicFactory> */
    use HasFactory;
    use SoftDeletes;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    // TODO refactor required_permissions to use RBAC
    // TODO populate body from the first forum topic comment
    protected $table = 'forum_topics';

    protected $fillable = [
        'forum_id',
        'title',
        'author_id',
        'latest_comment_id',
        'required_permissions',
        'pinned_at',
        'locked_at',
    ];

    protected $casts = [
        'pinned_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        // 'created' => ForumTopicCreated::class,
    ];

    protected $observables = [];

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'required_permissions',
                'pinned_at',
                'locked_at',
                'deleted_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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

    public function getIsLockedAttribute(): bool
    {
        return !empty($this->locked_at);
    }

    public function getSlugAttribute(): string
    {
        return ($this->forum->category->title ? '-' . Str::slug($this->forum->category->title) : '')
            . ($this->forum->title ? '-' . Str::slug($this->forum->title) : '')
            . ($this->title ? '-' . Str::slug($this->title) : '');
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, ForumTopic>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<Forum, ForumTopic>
     */
    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    /**
     * @return HasMany<ForumTopicComment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ForumTopicComment::class, 'forum_topic_id')->with('user');
    }

    /**
     * @return HasMany<ForumTopicComment>
     */
    public function visibleComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->comments()->visibleTo($currentUser);
    }

    /**
     * @return HasOne<ForumTopicComment>
     */
    public function latestComment(): HasOne
    {
        return $this->hasOne(ForumTopicComment::class, 'forum_topic_id')
            ->where('is_authorized', 1)
            ->orderByDesc('created_at');
    }

    // == scopes

    /**
     * Order by latest comment dynamic relationship
     *
     * @param Builder<ForumTopic> $query
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

    /**
     * @param Builder<ForumTopic> $query
     * @return Builder<ForumTopic>
     */
    public function scopeWithLatestComment(Builder $query): Builder
    {
        return $query->with(['latestComment.user']);
    }
}
