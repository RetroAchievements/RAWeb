<?php

declare(strict_types=1);

namespace LegacyApp\Platform\Models;

use Database\Factories\Legacy\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LegacyApp\Community\Models\Rating;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class Game extends BaseModel
{
    use HasFactory;

    protected $table = 'GameData';

    protected static function newFactory(): GameFactory
    {
        return GameFactory::new();
    }

    /**
     * @return HasMany<Achievement>
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class, 'GameID');
    }

    /**
     * @return HasMany<Rating>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'RatingID');
    }

    /**
     * @return BelongsTo<System, Game>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'ConsoleID');
    }

    /**
     * @return BelongsTo<System, Game>
     */
    public function console(): BelongsTo
    {
        return $this->system();
    }
}
