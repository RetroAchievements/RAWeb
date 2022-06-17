<?php

declare(strict_types=1);

namespace App\Support\Database\Eloquent;

use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

class BasePivotNonIncrementing extends BaseModel
{
    use AsPivot;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     */
    protected $guarded = [];
}
