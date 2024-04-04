<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementSetClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AchievementSetClaim extends BaseModel
{
    use HasFactory;

    // TODO rename SetClaim to achievement_set_claims
    // TODO rename ClaimType to claim_type
    // TODO rename SetType to set_type
    // TODO rename Status to claim_status
    // TODO rename Extension to extensions_count
    // TODO rename Special to special_type
    // TODO rename Finished to finished_at
    // TODO rename Created to created_at
    // TODO rename Updated to updated_at
    // TODO drop User, rely solely on user_id
    protected $table = 'SetClaim';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'User',
        'user_id',
        'game_id',
        'ClaimType',
        'SetType',
        'Status',
        'Extension',
        'Special',
        'Finished',
    ];

    protected $casts = [
        'Finished' => 'datetime',
    ];

    protected static function newFactory(): AchievementSetClaimFactory
    {
        return AchievementSetClaimFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, AchievementSetClaim>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    /**
     * @return BelongsTo<User, AchievementSetClaim>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
