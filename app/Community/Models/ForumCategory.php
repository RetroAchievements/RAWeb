<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class ForumCategory extends BaseModel
{
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
    ];

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
        // TODO: return $this->isPublished();
        return true;
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('forum-category.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('forum-category.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    // == mutators

    // == relations

    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class);
    }

    // == scopes
}
