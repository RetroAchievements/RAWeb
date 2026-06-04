<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementSetVersionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AchievementSetVersion extends BaseModel
{
    /** @use HasFactory<AchievementSetVersionFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'achievement_set_id',
        'version',
        'parent_id',
        'definition',
        'players_total',
        'players_hardcore',
        'achievements_published',
        'achievements_unpublished',
        'points_total',
        'created_at',
    ];

    /**
     * `definition` holds a snapshot of the set's achievement composition at version time:
     * ['version' => 1, 'achievements' => [['id', 'points', 'is_promoted', 'type'], ...]].
     *
     * Semantics are sparse. It is non-null only for (a) latest versions backfilled after the
     * snapshotting deploy and (b) versions created after that deploy. Older historical versions
     * stay null because their composition cannot be reconstructed accurately. A real previous
     * row whose definition is null means "unknown baseline," not "empty baseline."
     */
    protected $casts = [
        'definition' => 'array',
    ];

    protected static function newFactory(): AchievementSetVersionFactory
    {
        return AchievementSetVersionFactory::new();
    }

    // == accessors

    public function getIsInitialVersionAttribute(): bool
    {
        return $this->parent_id === null;
    }

    public function getIsLatestVersionAttribute(): bool
    {
        return !$this->nextVersion()->exists();
    }

    // == mutators

    // == relations

    /**
     * @return HasOne<AchievementSetVersion, $this>
     */
    public function nextVersion(): HasOne
    {
        return $this->hasOne(AchievementSetVersion::class, 'parent_id');
    }

    /**
     * @return BelongsTo<AchievementSetVersion, $this>
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(AchievementSetVersion::class, 'parent_id');
    }

    /**
     * @return BelongsTo<AchievementSet, $this>
     */
    public function achievementSet(): BelongsTo
    {
        return $this->belongsTo(AchievementSet::class, 'achievement_set_id', 'id')->withTrashed();
    }

    // == scopes

    /**
     * @param Builder<AchievementSetVersion> $query
     * @return Builder<AchievementSetVersion>
     */
    public function scopeLatestVersion(Builder $query): Builder
    {
        return $query->whereNotExists(function ($query) {
            $query->from('achievement_set_versions', 'asv2')
                ->whereColumn('asv2.parent_id', 'achievement_set_versions.id');
        });
    }

    /**
     * @param Builder<AchievementSetVersion> $query
     * @return Builder<AchievementSetVersion>
     */
    public function scopeInitialVersion(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @param Builder<AchievementSetVersion> $query
     * @return Builder<AchievementSetVersion>
     */
    public function scopeVersion(Builder $query, int $version): Builder
    {
        return $query->whereVersion($version);
    }
}
