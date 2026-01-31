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

    protected static function boot(): void
    {
        parent::boot();

        /**
         * Re-index topic posts when required_permissions changes so
         * they are added/removed from the search index appropriately.
         */
        static::updated(function (ForumTopic $forumTopic) {
            if ($forumTopic->wasChanged('required_permissions')) {
                $comments = $forumTopic->comments()->get();

                if ($forumTopic->required_permissions === 0) {
                    $comments->searchable();
                } else {
                    $comments->unsearchable();
                }
            }
        });

        static::deleted(function (ForumTopic $forumTopic) {
            $forumTopic->comments()->get()->unsearchable();
        });

        static::restored(function (ForumTopic $forumTopic) {
            if ($forumTopic->required_permissions === 0) {
                $forumTopic->comments()->get()->searchable();
            }
        });
    }

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

    // == constants

    public const int COMMENTS_PER_PAGE = 15;

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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<Forum, $this>
     */
    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }

    /**
     * @return HasMany<ForumTopicComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ForumTopicComment::class, 'forum_topic_id')->with('user');
    }

    /**
     * @return HasMany<ForumTopicComment, $this>
     */
    public function visibleComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->comments()->visibleTo($currentUser);
    }

    /**
     * @return HasOne<ForumTopicComment, $this>
     */
    public function latestComment(): HasOne
    {
        return $this->hasOne(ForumTopicComment::class, 'forum_topic_id')
            ->where('is_authorized', 1)
            ->orderByDesc('created_at');
    }

    // == scopes

    /**
     * @param Builder<ForumTopic> $query
     * @return Builder<ForumTopic>
     */
    public function scopeWithLatestComment(Builder $query): Builder
    {
        return $query->with(['latestComment.user']);
    }
}
