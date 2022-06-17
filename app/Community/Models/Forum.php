<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Forum extends BaseModel
{
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
    ];

    protected $with = [
        'category',
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
        // return $this->isPublished();
        return true;
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('forum.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('forum.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return ($this->category->title ? '-' . Str::slug($this->category->title) : '')
            . ($this->title ? '-' . Str::slug($this->title) : '');
    }

    // == mutators

    // == relations

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class);
    }

    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(ForumTopicComment::class, ForumTopic::class, 'forum_id', 'commentable_id')
            ->where('commentable_type', resource_type(static::class));
    }
}
