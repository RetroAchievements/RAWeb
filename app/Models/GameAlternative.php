<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAlternative extends BaseModel
{
    // TODO drop GameAlternatives, migrate to game_sets
    protected $table = 'GameAlternatives';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    // == accessors

    protected $visible = [
        'gameID',
        'gameIDAlt',
    ];

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, GameAlternative>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'gameID', 'ID');
    }

    /**
     * @return BelongsTo<Game, GameAlternative>
     */
    public function alternativeGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'gameIDAlt', 'ID');
    }

    // == scopes
}
