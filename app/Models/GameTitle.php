<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\GameTitleRegion;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameTitleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameTitle extends BaseModel
{
    /** @use HasFactory<GameTitleFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'game_titles';

    // Re-index games on save.
    protected $touches = ['game'];

    protected $fillable = [
        'game_id',
        'title',
        'region',
        'is_canonical',
    ];

    protected $casts = [
        'region' => GameTitleRegion::class,
        'is_canonical' => 'boolean',
    ];

    protected static function newFactory(): GameTitleFactory
    {
        return GameTitleFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, GameTitle>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    // == scopes
}
