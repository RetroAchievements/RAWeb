<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Model;
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

    /**
     * @return MorphTo<Model, Trigger>
     */
    public function triggerable(): MorphTo
    {
        return $this->morphTo();
    }
}
