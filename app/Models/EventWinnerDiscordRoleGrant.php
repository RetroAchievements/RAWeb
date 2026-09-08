<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\EventWinnerDiscordRoleGrantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventWinnerDiscordRoleGrant extends BaseModel
{
    /** @use HasFactory<EventWinnerDiscordRoleGrantFactory> */
    use HasFactory;

    protected $table = 'event_winner_discord_role_grants';

    protected $fillable = [
        'user_id',
        'discord_role_id',
        'discord_user_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function newFactory(): EventWinnerDiscordRoleGrantFactory
    {
        return EventWinnerDiscordRoleGrantFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    // == scopes
}
