<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\MessageThreadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Auth;

class MessageThread extends BaseModel
{
    /** @use HasFactory<MessageThreadFactory> */
    use HasFactory;

    protected $table = 'message_threads';

    protected $fillable = [
        'title',
        'last_message_id',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): MessageThreadFactory
    {
        return MessageThreadFactory::new();
    }

    // == accessors

    public function getIsUnreadAttribute(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $participant = $this->relationLoaded('participants')
            ? $this->participants->where('ID', Auth::id())->first()
            : $this->participants()->wherePivot('user_id', Auth::id())->first();

        return $participant && $participant->pivot->num_unread > 0;
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Message>
     */
    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    /**
     * @return HasMany<Message>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'thread_id');
    }

    /**
     * @return BelongsToMany<User>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'message_thread_participants',
            'thread_id',
            'user_id',
            'id',
            'ID'
        )
            ->withTimestamps()
            ->withPivot(['num_unread', 'deleted_at'])
            ->where(function ($query) {
                $query->where('message_thread_participants.user_id', '!=', Auth::id())
                    ->orWhere(function ($query) {
                        $query->where('message_thread_participants.user_id', Auth::id())
                            ->whereNull('message_thread_participants.deleted_at');
                    });
            });
    }

    /**
     * @return HasManyThrough<User>
     */
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            MessageThreadParticipant::class,
            'thread_id',
            'ID',
            'id',
            'user_id'
        );
    }

    // == scopes
}
