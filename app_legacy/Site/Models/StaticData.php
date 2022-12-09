<?php

declare(strict_types=1);

namespace LegacyApp\Site\Models;

use Database\Factories\Legacy\StaticDataFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class StaticData extends BaseModel
{
    use HasFactory;

    protected $table = 'StaticData';

    protected static $unguarded = true;

    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $dates = [
        'LastAchievementEarnedAt',
        'LastRegisteredUserAt',
        'Event_AOTW_StartAt',
    ];

    protected static function newFactory(): StaticDataFactory
    {
        return StaticDataFactory::new();
    }

    public function lastRegisteredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'LastRegisteredUser', 'User');
    }
}
