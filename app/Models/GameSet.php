<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\GameSetType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameSetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// TODO drop image_asset_path, migrate to media
class GameSet extends BaseModel
{
    /** @use HasFactory<GameSetFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'game_sets';

    protected $fillable = [
        'definition',
        'game_id',
        'image_asset_path',
        'title',
        'type',
        'updated_at',
        'user_id',
    ];

    protected $casts = [
        'type' => GameSetType::class,
    ];

    protected static function newFactory(): GameSetFactory
    {
        return GameSetFactory::new();
    }

    // == constants

    public const CentralHubId = 6591;

    // == accessors

    public function getBadgeUrlAttribute(): string
    {
        return media_asset($this->image_asset_path);
    }

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
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(GameSet::class, 'game_set_links', 'child_game_set_id', 'parent_game_set_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }

    /**
     * @return BelongsToMany<GameSet>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(GameSet::class, 'game_set_links', 'parent_game_set_id', 'child_game_set_id')
            ->withTimestamps()
            ->withPivot('created_at', 'updated_at');
    }

    // == scopes
}
