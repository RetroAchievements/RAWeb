<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementSetClaimFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AchievementSetClaim extends BaseModel
{
    use HasFactory;

    // TODO rename SetClaim to achievement_set_claims
    // TODO rename ClaimType to claim_type, remove getClaimTypeAttribute()
    // TODO rename SetType to set_type, remove getSetTypeAttribute()
    // TODO rename Status to status, remove getStatusAttribute()
    // TODO rename Extension to extensions_count
    // TODO rename Special to special_type
    // TODO rename Finished to finished_at, remove getFinishedAtAttribute()
    // TODO rename Created to created_at, remove getCreatedAtAttribute()
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

    // TODO remove after rename from "ClaimType" to "claim_type"
    public function getClaimTypeAttribute(): int
    {
        return $this->attributes['ClaimType'];
    }

    // TODO remove after rename from "Created" to "created_at"
    public function getCreatedAtAttribute(): Carbon
    {
        return Carbon::parse($this->attributes['Created']);
    }

    // TODO remove after rename from "Finished" to "finished_at"
    public function getFinishedAtAttribute(): Carbon
    {
        return Carbon::parse($this->attributes['Finished']);
    }

    public function getMinutesActiveAttribute(): int
    {
        return $this->created_at->diffInMinutes();
    }

    public function getMinutesLeftAttribute(): int
    {
        return $this->finished_at->diffInMinutes();
    }

    // TODO remove after rename from "SetType" to "set_type"
    public function getSetTypeAttribute(): int
    {
        return $this->attributes['SetType'];
    }

    // TODO remove after rename from "Status" to "status"
    public function getStatusAttribute(): int
    {
        return $this->attributes['Status'] ?? null;
    }

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

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeClaimType(Builder $query, int $claimType): Builder
    {
        return $query->where('ClaimType', $claimType);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopePrimaryClaim(Builder $query): Builder
    {
        return $this->scopeClaimType($query, ClaimType::Primary);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeCollaborationClaim(Builder $query): Builder
    {
        return $this->scopeClaimType($query, ClaimType::Collaboration);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeSetType(Builder $query, int $setType): Builder
    {
        return $query->where('SetType', $setType);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeNewSet(Builder $query): Builder
    {
        return $this->scopeSetType($query, ClaimSetType::NewSet);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeRevisionSet(Builder $query): Builder
    {
        return $this->scopeSetType($query, ClaimSetType::Revision);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeStatus(Builder $query, int $claimStatus): Builder
    {
        return $query->where('Status', $claimStatus);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeStatus($query, ClaimStatus::Active);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeComplete(Builder $query): Builder
    {
        return $this->scopeStatus($query, ClaimStatus::Complete);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeDropped(Builder $query): Builder
    {
        return $this->scopeStatus($query, ClaimStatus::Dropped);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeInReview(Builder $query): Builder
    {
        return $this->scopeStatus($query, ClaimStatus::InReview);
    }

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeActiveOrInReview(Builder $query): Builder
    {
        return $query->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview]);
    }
}
