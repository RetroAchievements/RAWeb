<?php

declare(strict_types=1);

namespace App\Site\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\StaticDataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaticData extends BaseModel
{
    use HasFactory;

    // TODO drop StaticData table
    protected $table = 'StaticData';

    protected static $unguarded = true;

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'LastAchievementEarnedAt' => 'datetime',
        'LastRegisteredUserAt' => 'datetime',
        'Event_AOTW_StartAt' => 'datetime',
    ];

    protected static function newFactory(): StaticDataFactory
    {
        return StaticDataFactory::new();
    }

    /**
     * @return BelongsTo<User, StaticData>
     */
    public function lastRegisteredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'LastRegisteredUser', 'User');
    }
}
