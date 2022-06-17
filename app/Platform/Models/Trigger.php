<?php

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trigger extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'conditions',
        'version',
        'parent_id',
    ];

    // == relations

    public function triggerable(): MorphTo
    {
        return $this->morphTo();
    }
}
