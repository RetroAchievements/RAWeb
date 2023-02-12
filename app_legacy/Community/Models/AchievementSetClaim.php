<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use Database\Factories\Legacy\AchievementSetClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class AchievementSetClaim extends BaseModel
{
    use HasFactory;

    protected $table = 'SetClaim';

    protected $dates = [
        'Finished',
    ];

    protected static function newFactory(): AchievementSetClaimFactory
    {
        return AchievementSetClaimFactory::new();
    }
}
