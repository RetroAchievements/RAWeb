<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Leaderboard extends BaseModel
{
    use Searchable;
    use SoftDeletes;

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'title',
            'description',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // return $this->isPublished();
        return true;
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('leaderboard.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('leaderboard.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    // == mutators

    // == relations

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
