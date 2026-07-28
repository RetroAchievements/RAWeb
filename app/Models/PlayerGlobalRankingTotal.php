<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\RankType;
use App\Support\Database\Eloquent\BaseModel;

class PlayerGlobalRankingTotal extends BaseModel
{
    public const UPDATED_AT = null;

    protected $primaryKey = 'rank_type';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'rank_type',
        'total',
    ];

    protected $casts = [
        'rank_type' => RankType::class,
        'total' => 'integer',
    ];

    // == helpers

    public static function forRankType(RankType $rankType): int
    {
        return (int) static::query()
            ->where('rank_type', $rankType)
            ->value('total');
    }

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
