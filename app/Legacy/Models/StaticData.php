<?php

declare(strict_types=1);

namespace App\Legacy\Models;

use App\Legacy\Database\Eloquent\BaseModel;

class StaticData extends BaseModel
{
    protected $table = 'StaticData';

    public $timestamps = false;

    protected $dates = [
        'LastAchievementEarnedAt',
        'LastRegisteredUserAt',
        'Event_AOTW_StartAt',
    ];
}
