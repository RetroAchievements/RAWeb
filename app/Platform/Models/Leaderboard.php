<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Leaderboard extends BaseModel
{
    use Searchable;
    use SoftDeletes;

    // TODO rename LeaderboardDef table to leaderboards
    // TODO rename ID column to id
    // TODO rename GameID column to game_id
    // TODO rename Format column to format
    // TODO rename LowerIsBetter column to rank_asc
    // TODO rename DisplayOrder column to order_column
    // TODO rename Created column to created_at
    // TODO rename Updated column to updated_at
    // TODO drop Mem, migrate to triggerable morph
    // TODO drop Author, migrate to triggerable morph author
    protected $table = 'LeaderboardDef';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

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

    public function getCanonicalUrlAttribute(): string
    {
        return route('leaderboard.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('leaderboard.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, Leaderboard>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
