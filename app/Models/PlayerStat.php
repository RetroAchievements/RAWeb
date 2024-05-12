<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\PlayerStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayerStat extends BaseModel
{
    use HasFactory;

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

    protected static function newFactory(): PlayerStatFactory
    {
        return PlayerStatFactory::new();
    }

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
