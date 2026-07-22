<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\OAuthGrantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OAuthGrant extends BaseModel
{
    /** @use HasFactory<OAuthGrantFactory> */
    use HasFactory;

    protected $table = 'oauth_grants';

    protected $casts = [
        'scopes' => 'array',
        'first_granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'scopes',
        'first_granted_at',
        'revoked_at',
    ];

    protected static function newFactory(): OAuthGrantFactory
    {
        return OAuthGrantFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<OAuthClient, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(OAuthClient::class, 'client_id');
    }

    // == scopes
}
