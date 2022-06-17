<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Site\Models\User;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemoryNote extends BaseModel
{
    use SoftDeletes;

    // == accessors

    // == mutators

    // == relations

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // == scopes
}
