<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\CommentableType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\CommentFactory;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Comment extends BaseModel
{
    use SoftDeletes;
    use Searchable;
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    // TODO use commentable morph
    protected $table = 'comments';

    public const SYSTEM_USER_ID = 14188;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'body',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'commentable_type' => CommentableType::class,
    ];

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    // == search

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
            'body' => $this->body,
            'created_at' => $this->created_at,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        // Don't index deleted comments.
        if ($this->deleted_at) {
            return false;
        }

        // Don't index automated system comments.
        if ($this->user_id === self::SYSTEM_USER_ID) {
            return false;
        }

        // Don't index comments from banned or deleted users.
        $this->loadMissing('userWithTrashed');
        $user = $this->userWithTrashed;
        if ($user->isBanned() || $user->trashed()) {
            return false;
        }

        // Don't index empty or extremely short comments (3 chars or less).
        $trimmedBody = trim($this->body);
        if (empty($trimmedBody) || mb_strlen($trimmedBody) <= 3) {
            return false;
        }

        // Don't index certain management comment types.
        $excludedTypes = [
            CommentableType::UserModeration,
            CommentableType::GameHash,
            CommentableType::SetClaim,
            CommentableType::GameModification,
        ];
        if (in_array($this->commentable_type, $excludedTypes, true)) {
            return false;
        }

        // Don't index user wall comments if the wall owner has disabled their wall or is banned.
        if ($this->commentable_type === CommentableType::User) {
            $wallOwner = User::find($this->commentable_id);
            if (!$wallOwner || !$wallOwner->UserWallActive || $wallOwner->isBanned()) {
                return false;
            }
        }

        return true;
    }

    // == accessors

    public function getEditLinkAttribute(): string
    {
        $this->notImplementedException();

        return '';
    }

    public function getPermalinkAttribute(): string
    {
        $this->notImplementedException();

        return '';
    }

    /**
     * @throws Exception
     */
    private function notImplementedException(): void
    {
        throw new Exception('Use derived comment model class in the comments() morphTo() relationship instead of ' . Comment::class . '. Add link attribute getters to the derived class. Use A dedicated controller for it and use the prepared actions.');
    }

    public function getIsAutomatedAttribute(): bool
    {
        return $this->user_id === self::SYSTEM_USER_ID;
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->commentable_type->supportsCommentRedirect()) {
            return route('comment.show', ['comment' => $this->id]);
        }

        if ($this->commentable_type === CommentableType::AchievementTicket) {
            return route('ticket.show', ['ticket' => $this->commentable_id]) . "#comment_{$this->id}";
        }

        return null;
    }

    // == mutators

    // == relations

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID')->withDefault(['username' => 'Deleted User', 'display_name' => 'Deleted User']);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function userWithTrashed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID')
            ->withTrashed()
            ->withDefault(['username' => 'Deleted User', 'display_name' => 'Deleted User']);
    }

    // == scopes

    /**
     * @param Builder<Comment> $query
     * @return Builder<Comment>
     */
    public function scopeAutomated(Builder $query): Builder
    {
        return $query->where('user_id', self::SYSTEM_USER_ID);
    }

    /**
     * @param Builder<Comment> $query
     * @return Builder<Comment>
     */
    public function scopeNotAutomated(Builder $query): Builder
    {
        return $query->where('user_id', '!=', self::SYSTEM_USER_ID);
    }

    /**
     * @param Builder<Comment> $query
     * @return Builder<Comment>
     */
    public function scopeVisibleTo(Builder $query, ?User $currentUser = null): Builder
    {
        if (!$currentUser || !$currentUser->hasRole([Role::ADMINISTRATOR, Role::MODERATOR])) {
            // If the current user isn't a moderator, exclude comments from banned users.
            $query->whereHas('userWithTrashed', function ($q) {
                $q->whereNull('banned_at');
            });
        }

        return $query;
    }
}
