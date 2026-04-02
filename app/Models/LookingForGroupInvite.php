<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\LookingForGroupInviteStatus;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $looking_for_group_post_id
 * @property int $sender_user_id
 * @property int $recipient_user_id
 * @property LookingForGroupInviteStatus $status
 * @property string|null $message
 * @property \Carbon\Carbon $sent_at
 * @property \Carbon\Carbon|null $responded_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read LookingForGroupPost $lookingForGroupPost
 * @property-read User $sender
 * @property-read User $recipient
 */
class LookingForGroupInvite extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'looking_for_group_post_id',
        'sender_user_id',
        'recipient_user_id',
        'status',
        'message',
        'sent_at',
        'responded_at',
        'expires_at',
    ];

    protected $casts = [
        'status' => LookingForGroupInviteStatus::class,
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $invite) {
            $invite->sent_at ??= now();
            $invite->status ??= LookingForGroupInviteStatus::Pending;
            
            // Default expiration to 7 days if not set
            if (!$invite->expires_at) {
                $invite->expires_at = now()->addDays(7);
            }
        });

        static::updating(function (self $invite) {
            // Set responded_at when status changes from pending
            if ($invite->isDirty('status') && 
                $invite->getOriginal('status') === LookingForGroupInviteStatus::Pending->value &&
                $invite->status !== LookingForGroupInviteStatus::Pending) {
                $invite->responded_at = now();
            }
        });
    }

    /**
     * Get the LFG post for this invite.
     */
    public function lookingForGroupPost(): BelongsTo
    {
        return $this->belongsTo(LookingForGroupPost::class);
    }

    /**
     * Get the sender user.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    /**
     * Get the recipient user.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * Scope to invites for a specific user (either sender or recipient).
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('sender_user_id', $user->id)
              ->orWhere('recipient_user_id', $user->id);
        });
    }

    /**
     * Scope to invites sent by a user.
     */
    public function scopeSentBy(Builder $query, User $user): Builder
    {
        return $query->where('sender_user_id', $user->id);
    }

    /**
     * Scope to invites received by a user.
     */
    public function scopeReceivedBy(Builder $query, User $user): Builder
    {
        return $query->where('recipient_user_id', $user->id);
    }

    /**
     * Scope to invites for a specific LFG post.
     */
    public function scopeForPost(Builder $query, LookingForGroupPost $post): Builder
    {
        return $query->where('looking_for_group_post_id', $post->id);
    }

    /**
     * Scope to invites with a specific status.
     */
    public function scopeWithStatus(Builder $query, LookingForGroupInviteStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Scope to non-expired invites.
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if the invite is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the user can perform an action on this invite.
     */
    public function canBeActedOnBy(User $user): bool
    {
        // Sender can cancel pending invites
        if ($this->sender_user_id === $user->id && $this->status === LookingForGroupInviteStatus::Pending) {
            return true;
        }

        // Recipient can accept/decline pending invites
        if ($this->recipient_user_id === $user->id && $this->status === LookingForGroupInviteStatus::Pending) {
            return true;
        }

        return false;
    }
}
