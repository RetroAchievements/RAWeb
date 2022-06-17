<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Ticket extends BaseModel
{
    use Searchable;
    use SoftDeletes;

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'body',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // return $this->isPublished();
        return true;
    }

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
