<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStat extends BaseModel
{
    protected $table = 'player_stats';

    protected $fillable = [
        'user_id',
        'system_id',
        'last_game_id',
        'type',
        'value',
        'stat_updated_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'system_id' => 'integer',
        'last_game_id' => 'integer',
        'stat_updated_at' => 'datetime',
        'value' => 'integer',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<System, PlayerStat>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class, 'system_id');
    }

    /**
     * @return BelongsTo<User, PlayerStat>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // == scopes
}
