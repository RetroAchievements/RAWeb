<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\LookingForGroupInviteStatus;
use App\Community\Enums\LookingForGroupStatus;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $game_id
 * @property int $creator_user_id
 * @property string $title
 * @property string|null $note
 * @property int|null $max_players
 * @property \Carbon\Carbon|null $scheduled_for
 * @property LookingForGroupStatus $status
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Game $game
 * @property-read User $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<LookingForGroupInvite> $invites
 */
class LookingForGroupPost extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'creator_user_id',
        'title',
        'note',
        'max_players',
        'scheduled_for',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'status' => LookingForGroupStatus::class,
        'scheduled_for' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $post) {
            $post->status ??= LookingForGroupStatus::Active;

            // Default expiration to 30 days if not set
            if (!$post->expires_at) {
                $post->expires_at = now()->addDays(30);
            }
        });

        static::updating(function (self $post) {
            // Auto-mark as expired if expiration time has passed
            if ($post->expires_at && $post->expires_at->isPast() && $post->status === LookingForGroupStatus::Active) {
                $post->status = LookingForGroupStatus::Expired;
            }
        });
    }

    /**
     * Get the game for this LFG post.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the creator user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Get the invites for this LFG post.
     */
    public function invites(): HasMany
    {
        return $this->hasMany(LookingForGroupInvite::class, 'looking_for_group_post_id');
    }

    /**
     * Get the accepted invites for this LFG post.
     */
    public function acceptedInvites(): HasMany
    {
        return $this->invites()->where('status', LookingForGroupInviteStatus::Accepted);
    }

    /**
     * Scope to active posts only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', LookingForGroupStatus::Active)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to posts for a specific game.
     */
    public function scopeForGame(Builder $query, Game $game): Builder
    {
        return $query->where('game_id', $game->id);
    }

    /**
     * Scope to posts created by a specific user.
     */
    public function scopeCreatedBy(Builder $query, User $user): Builder
    {
        return $query->where('creator_user_id', $user->id);
    }

    /**
     * Scope to posts scheduled for a specific date range.
     */
    public function scopeScheduledBetween(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('scheduled_for', [$start, $end]);
    }

    /**
     * Scope to posts that have space available.
     */
    public function scopeHasSpace(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('max_players')
              ->orWhereHas('acceptedInvites', function ($subQ) {
                  $subQ->havingRaw('COUNT(*) < looking_for_group_posts.max_players');
              }, '>');
        });
    }

    /**
     * Check if the post is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the post has reached max players.
     */
    public function isFull(): bool
    {
        if (!$this->max_players) {
            return false;
        }

        return $this->acceptedInvites()->count() >= $this->max_players;
    }

    /**
     * Get the number of accepted players.
     */
    public function getAcceptedPlayersCount(): int
    {
        return $this->acceptedInvites()->count();
    }

    /**
     * Get the number of available slots.
     */
    public function getAvailableSlotsCount(): int
    {
        if (!$this->max_players) {
            return 999; // Unlimited
        }

        return max(0, $this->max_players - $this->getAcceptedPlayersCount());
    }

    /**
     * Check if a user can join this post.
     */
    public function canBeJoinedBy(User $user): bool
    {
        // Creator can't join their own post
        if ($this->creator_user_id === $user->id) {
            return false;
        }

        // Must be active and not expired
        if ($this->status !== LookingForGroupStatus::Active || $this->isExpired()) {
            return false;
        }

        // Must have space available
        if ($this->isFull()) {
            return false;
        }

        // User must not already have an invite
        return !$this->invites()->where('sender_user_id', $user->id)->exists();
    }
}
