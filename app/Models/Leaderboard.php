<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\LeaderboardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Leaderboard extends BaseModel
{
    /*
     * Shared Traits
     */
    use HasFactory;

    use Searchable;
    use SoftDeletes;

    // TODO rename LeaderboardDef table to leaderboards
    // TODO rename ID column to id
    // TODO rename GameID column to game_id
    // TODO rename Format column to format, remove getFormatAttribute()
    // TODO rename Title column to title, remove getTitleAttribute()
    // TODO rename Description column to description, remove getDescriptionAttribute()
    // TODO rename LowerIsBetter column to rank_asc, remove getRankAscAttribute()
    // TODO rename DisplayOrder column to order_column, remove getOrderColumnAttribute()
    // TODO rename Created column to created_at, set to non-nullable, remove getCreatedAtAttribute()
    // TODO rename Updated column to updated_at, set to non-nullable, remove getUpdatedAtAttribute()
    // TODO drop Mem, migrate to triggerable morph
    // TODO drop Author and author_id, migrate to triggerable morph author
    protected $table = 'LeaderboardDef';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected static function newFactory(): LeaderboardFactory
    {
        return LeaderboardFactory::new();
    }

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'ID',
            'Title',
            'Description',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO return true;
        return false;
    }

    // == accessors

    // TODO remove after rename
    public function getCreatedAtAttribute(): Carbon
    {
        return $this->attributes['Created'] ? Carbon::parse($this->attributes['Created']) : Carbon::now();
    }

    // TODO remove after rename
    public function getIdAttribute(): int
    {
        return $this->attributes['ID'];
    }

    public function getCanonicalUrlAttribute(): string
    {
        return route('leaderboard.show', [$this, $this->getSlugAttribute()]);
    }

    // TODO remove after rename
    public function getDescriptionAttribute(): string
    {
        return $this->attributes['Description'] ?? '';
    }

    // TODO remove after rename
    public function getFormatAttribute(): ?string
    {
        return $this->attributes['Format'] ?? null;
    }

    // TODO remove after rename
    public function getOrderColumnAttribute(): int
    {
        return $this->attributes['DisplayOrder'];
    }

    public function getPermalinkAttribute(): string
    {
        return route('leaderboard.show', $this);
    }

    // TODO remove after rename
    public function getRankAscAttribute(): bool
    {
        return $this->attributes['LowerIsBetter'] === 1;
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    // TODO remove after rename
    public function getTitleAttribute(): string
    {
        return $this->attributes['Title'] ?? '';
    }

    // TODO remove after rename
    public function getUpdatedAtAttribute(): Carbon
    {
        return $this->attributes['Updated'] ? Carbon::parse($this->attributes['Updated']) : Carbon::now();
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, Leaderboard>
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'ID');
    }

    /**
     * @return HasMany<LeaderboardEntry>
     */
    public function entries(bool $includeUnrankedUsers = false): HasMany
    {
        return $this->hasMany(LeaderboardEntry::class, 'leaderboard_id')
            ->whereHas('user', function ($query) use ($includeUnrankedUsers) {
                if (!$includeUnrankedUsers) {
                    $query->where('Untracked', '!=', 1)
                        ->whereNull('unranked_at');
                }
            });
    }

    /**
     * @return BelongsTo<Game, Leaderboard>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'GameID', 'ID');
    }
}
