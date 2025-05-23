<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Actions\SyncGameReleaseDatesAction;
use App\Platform\Enums\GameReleaseRegion;
use App\Platform\Enums\ReleasedAtGranularity;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameReleaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameRelease extends BaseModel
{
    /** @use HasFactory<GameReleaseFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'game_releases';

    // Re-index games on save.
    protected $touches = ['game'];

    protected $fillable = [
        'game_id',
        'released_at',
        'released_at_granularity',
        'title',
        'region',
        'is_canonical_game_title',
    ];

    protected $casts = [
        'is_canonical_game_title' => 'boolean',
        'region' => GameReleaseRegion::class,
        'released_at_granularity' => ReleasedAtGranularity::class,
        'released_at' => 'datetime',
    ];

    protected static function newFactory(): GameReleaseFactory
    {
        return GameReleaseFactory::new();
    }

    public static function boot()
    {
        parent::boot();

        static::saved(function (GameRelease $gameRelease) {
            // Sync game release dates whenever a GameRelease is created or updated
            (new SyncGameReleaseDatesAction())->execute($gameRelease->game);
        });

        static::deleted(function (GameRelease $gameRelease) {
            // Sync game release dates when a GameRelease is deleted
            (new SyncGameReleaseDatesAction())->execute($gameRelease->game);
        });
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, GameRelease>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    // == scopes
}
