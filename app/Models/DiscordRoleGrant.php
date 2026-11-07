<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\DiscordRoleGrantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordRoleGrant extends BaseModel
{
    /** @use HasFactory<DiscordRoleGrantFactory> */
    use HasFactory;

    protected $table = 'discord_role_grants';

    protected $fillable = [
        'user_id',
        'discord_role_id',
        'discord_user_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function newFactory(): DiscordRoleGrantFactory
    {
        return DiscordRoleGrantFactory::new();
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
