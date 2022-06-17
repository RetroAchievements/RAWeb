<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BasePivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerAchievement extends BasePivot
{
    use SoftDeletes;

    protected $table = 'player_achievements';

    protected $fillable = [
        'user_id',
        'achievement_id',
        'trigger_id',
        'player_session_id',
        'unlocked_at',
        'unlocked_hardcore_at',
        'unlocked_by_user_id',
    ];

    protected $casts = [
        'unlocked_at' => 'datetime',
        'unlocked_hardcore_at' => 'datetime',
        'unlocked_by_user_id' => 'int',
    ];

    // == accessors

    // == mutators

    // == relations

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Site\Models\User::class);
    }

    // == scopes
}
