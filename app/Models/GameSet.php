<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameSet extends BaseModel
{
    use SoftDeletes;

    protected $table = 'game_sets';

    protected $fillable = [
        'definition',
        'game_id',
        'title',
        'type',
        'updated_at',
        'user_id',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, GameSet>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    /**
     * @return BelongsToMany<Game>
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_set_games', 'game_set_id', 'game_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at', 'deleted_at');
    }

    /**
     * @return BelongsToMany<GameSet>
     */
    public function links(): BelongsToMany
    {
        return $this->belongsToMany(GameSet::class, 'game_set_links', 'parent_game_set_id', 'child_game_set_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }

    // == scopes
}
