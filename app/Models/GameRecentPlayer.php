<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameRecentPlayerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRecentPlayer extends BaseModel
{
    /** @use HasFactory<GameRecentPlayerFactory> */
    use HasFactory;

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

    protected static function newFactory(): GameRecentPlayerFactory
    {
        return GameRecentPlayerFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // == scopes
}
