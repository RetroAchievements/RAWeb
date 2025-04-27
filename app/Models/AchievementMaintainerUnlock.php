<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AchievementMaintainerUnlock extends BaseModel
{
    protected $table = 'achievement_maintainer_unlocks';

    protected $fillable = [
        'player_achievement_id',
        'user_id',
        'maintainer_id',
        'achievement_id',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<PlayerAchievement, AchievementMaintainerUnlock>
     */
    public function playerAchievement(): BelongsTo
    {
        return $this->belongsTo(PlayerAchievement::class, 'player_achievement_id', 'id');
    }

    /**
     * @return BelongsTo<User, AchievementMaintainerUnlock>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    /**
     * @return BelongsTo<User, AchievementMaintainerUnlock>
     */
    public function maintainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maintainer_id', 'ID');
    }

    /**
     * @return BelongsTo<Achievement, AchievementMaintainerUnlock>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'achievement_id', 'ID');
    }

    // == scopes
}
