<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;

class PlayerGlobalRankingTotal extends BaseModel
{
    public const UPDATED_AT = null;

    /**
     * 1 = hardcore, 2 = casual, 3 = weighted (hardcore)
     */
    protected $primaryKey = 'rank_type';

    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'rank_type',
        'total',
    ];

    protected $casts = [
        'rank_type' => 'integer',
        'total' => 'integer',
    ];

    // == helpers

    public static function forRankType(int $rankType): int
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
