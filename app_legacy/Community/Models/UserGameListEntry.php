<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use Database\Factories\Legacy\UserGameListEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LegacyApp\Platform\Models\Game;
use LegacyApp\Site\Models\User;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class UserGameListEntry extends BaseModel
{
    use HasFactory;

    protected $table = 'SetRequest';

    protected $fillable = [
        'User',
        'GameID',
    ];

    protected $casts = [
        'GameID' => 'integer',
    ];

    public const CREATED_AT = 'Updated';
    public const UPDATED_AT = null;

    protected static function newFactory(): UserGameListEntryFactory
    {
        return UserGameListEntryFactory::new();
    }

    /**
     * @return BelongsTo<User, UserGameListEntry>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'User', 'User');
    }

    /**
     * @return BelongsTo<Game, UserGameListEntry>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'GameID');
    }

    /**
     * @param Builder<UserGameListEntry> $query
     * @return Builder<UserGameListEntry>
     */
    public function scopeWithoutAchievements(Builder $query): Builder
    {
        return $query
            ->select('SetRequest.*')
            ->leftJoin('Achievements', 'SetRequest.GameID', '=', 'Achievements.GameID')
            ->groupBy('Achievements.GameID')
            ->havingRaw('count(Achievements.ID) = 0');
    }
}
