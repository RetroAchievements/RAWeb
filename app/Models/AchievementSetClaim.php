<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
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
    /** @use HasFactory<AchievementSetClaimFactory> */
    use HasFactory;

    public const MAX_CLAIMS_JUNIOR_DEVELOPER = 1;
    public const MAX_CLAIMS_DEVELOPER = 4;

    protected $table = 'achievement_set_claims';

    protected $fillable = [
        'user_id',
        'game_id',
        'claim_type',
        'set_type',
        'status',
        'extensions_count',
        'special_type',
        'finished_at',
    ];

    protected $casts = [
        'claim_type' => ClaimType::class,
        'set_type' => ClaimSetType::class,
        'status' => ClaimStatus::class,
        'special_type' => ClaimSpecial::class,
        'finished_at' => 'datetime',
    ];

    protected static function newFactory(): AchievementSetClaimFactory
    {
        return AchievementSetClaimFactory::new();
    }

    // == accessors

    public function getMinutesActiveAttribute(): int
    {
        return (int) $this->created_at->diffInMinutes(Carbon::now());
    }

    public function getMinutesLeftAttribute(): int
    {
        return (int) $this->finished_at->diffInMinutes(Carbon::now());
    }

    public function getUserLastPlayedAtAttribute(): ?\Carbon\Carbon
    {
        if (!$this->user_id || !$this->game_id) {
            return null;
        }

        $playerSession = PlayerSession::query()
            ->where('game_id', $this->game_id)
            ->where('user_id', $this->user_id)
            ->orderByDesc('rich_presence_updated_at')
            ->first(['rich_presence_updated_at']);

        return $playerSession?->rich_presence_updated_at;
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    // == scopes

    /**
     * @param Builder<AchievementSetClaim> $query
     * @return Builder<AchievementSetClaim>
     */
    public function scopeClaimType(Builder $query, ClaimType $claimType): Builder
    {
        return $query->where('claim_type', $claimType);
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
    public function scopeSetType(Builder $query, ClaimSetType $setType): Builder
    {
        return $query->where('set_type', $setType);
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
    public function scopeStatus(Builder $query, ClaimStatus $claimStatus): Builder
    {
        return $query->where('status', $claimStatus);
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
    public function scopeActiveClaimForGameId(Builder $query, int $gameId): Builder
    {
        return $query->where('game_id', $gameId)->active();
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
        return $query->whereIn('status', [ClaimStatus::Active, ClaimStatus::InReview]);
    }

    // == helpers

    public static function getMaxClaimsForUser(User $user): int
    {
        if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return self::MAX_CLAIMS_JUNIOR_DEVELOPER;
        }

        if ($user->hasRole(Role::DEVELOPER)) {
            return self::MAX_CLAIMS_DEVELOPER;
        }

        return 0;
    }
}
