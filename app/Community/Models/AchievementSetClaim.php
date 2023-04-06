<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\AchievementSetClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AchievementSetClaim extends BaseModel
{
    use HasFactory;

    // TODO rename SetClaim to achievement_set_claims
    protected $table = 'SetClaim';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $casts = [
        'Finished' => 'datetime',
    ];

    protected static function newFactory(): AchievementSetClaimFactory
    {
        return AchievementSetClaimFactory::new();
    }
}
