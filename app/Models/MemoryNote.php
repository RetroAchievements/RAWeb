<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MemoryNote extends BaseModel
{
    use SoftDeletes;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    // TODO drop game_id, migrate to game_hash_set_id
    protected $table = 'memory_notes';

    protected $fillable = [
        'user_id',
        'game_id',
        'address',
        'body',
    ];

    protected $visible = [
        'game_id',
        'address',
        'body',
    ];

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'user_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    public function getAddressHexAttribute(): string
    {
        // 16399 -> "0x00400f"
        return '0x' . str_pad(dechex($this->address), 6, '0', STR_PAD_LEFT);
    }

    // == mutators

    // == relations

    /**
     * TODO will need to be modified if GameID is migrated to game_hash_set_id
     *
     * @return BelongsTo<Game, MemoryNote>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'ID');
    }

    /**
     * @return BelongsTo<User, MemoryNote>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
