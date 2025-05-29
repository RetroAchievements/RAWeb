<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Actions\FormatGameReleaseDateAction;
use App\Platform\Actions\LogGameReleaseActivityAction;
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

        static::created(function (GameRelease $gameRelease) {
            // Log release creates to the game's activity log.
            (new LogGameReleaseActivityAction())->execute('create', $gameRelease);

            // Sync game release dates whenever a GameRelease is created.
            (new SyncGameReleaseDatesAction())->execute($gameRelease->game);
        });

        static::updated(function (GameRelease $gameRelease) {
            // Log release updates to the game's activity log.
            $original = $gameRelease->getOriginal();
            $changes = $gameRelease->getChanges();
            (new LogGameReleaseActivityAction())->execute('update', $gameRelease, $original, $changes);

            // Sync game release dates whenever a GameRelease is updated.
            (new SyncGameReleaseDatesAction())->execute($gameRelease->game);
        });

        static::deleted(function (GameRelease $gameRelease) {
            // Log release deletes to the game's activity log.
            (new LogGameReleaseActivityAction())->execute('delete', $gameRelease);

            // Sync game release dates when a GameRelease is deleted.
            (new SyncGameReleaseDatesAction())->execute($gameRelease->game);
        });
    }

    // == accessors

    public function getFormattedReleaseDateAttribute(): ?string
    {
        return (new FormatGameReleaseDateAction())->execute(
            $this->released_at,
            $this->released_at_granularity
        );
    }

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
