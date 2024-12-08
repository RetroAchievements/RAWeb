<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameSetLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSetLink extends BaseModel
{
    /** @use HasFactory<GameSetLinkFactory> */
    use HasFactory;

    protected $table = 'game_set_links';

    protected $fillable = [
        'child_game_set_id',
        'parent_game_set_id',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory(): GameSetLinkFactory
    {
        return GameSetLinkFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<GameSet, GameSetLink>
     */
    public function parentGameSet(): BelongsTo
    {
        return $this->belongsTo(GameSet::class, 'parent_game_set_id');
    }

    /**
     * @return BelongsTo<GameSet, GameSetLink>
     */
    public function childGameSet(): BelongsTo
    {
        return $this->belongsTo(GameSet::class, 'child_game_set_id');
    }

    // == scopes
}
