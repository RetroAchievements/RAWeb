<?php

declare(strict_types=1);

namespace App\Models;

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

    // TODO rename Comment table to comments
    // TODO rename ID column id
    // TODO drop ArticleType, migrate to commentable_type (morph map)
    // TODO drop ArticleID, migrate to commentable_id
    // TODO rename Payload to body or payload
    // TODO rename Submitted to created_at
    // TODO rename Edited to updated_at
    protected $table = 'Comment';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Submitted';
    public const UPDATED_AT = 'Edited';

    public const SYSTEM_USER_ID = 14188;

    protected $fillable = [
        'ArticleType',
        'ArticleID',
        'Payload',
        'user_id',
        'Submitted',
    ];

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }

    // == search

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->ID,
            'user_id' => $this->user_id,
            'ArticleType' => $this->ArticleType,
            'ArticleID' => $this->ArticleID,
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
            'body' => $this->Payload, // this is fine, as of 2025-05-18, 99.88% of comments are <1KB
            'created_at' => $this->Submitted,
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

        // Don't index comments from banned users.
        $this->loadMissing('userWithTrashed');
        $user = $this->userWithTrashed;
        if ($user->banned_at !== null) {
            return false;
        }

        // Don't index empty or extremely short comments (3 chars or less).
        $trimmedPayload = trim($this->Payload);
        if (empty($trimmedPayload) || mb_strlen($trimmedPayload) <= 3) {
            return false;
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

    // == mutators

    // == relations

    /**
     * @return MorphTo<Model, Comment>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, Comment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID')->withDefault(['username' => 'Deleted User']);
    }

    /**
     * @return BelongsTo<User, Comment>
     */
    public function userWithTrashed(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID')
            ->withTrashed()
            ->withDefault(['username' => 'Deleted User']);
    }

    // == scopes

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
     */
    public function scopeAutomated(Builder $query): Builder
    {
        return $query->where('user_id', self::SYSTEM_USER_ID);
    }

    /**
     * @param Builder<Achievement> $query
     * @return Builder<Achievement>
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
