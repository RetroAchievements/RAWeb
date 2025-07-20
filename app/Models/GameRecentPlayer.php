<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRecentPlayer extends BaseModel
{
    protected $table = 'game_recent_players';

    protected $fillable = [
        'game_id',
        'user_id',
        'rich_presence',
        'rich_presence_updated_at',
    ];

    protected $casts = [
        'rich_presence_updated_at' => 'datetime',
    ];

    public $timestamps = false;

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, GameRecentPlayer>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    /**
     * @return BelongsTo<User, GameRecentPlayer>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
