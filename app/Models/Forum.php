<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\ForumFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Forum extends BaseModel
{
    /** @use HasFactory<ForumFactory> */
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    // TODO drop latest_comment_id -> derived
    protected $table = 'forums';

    protected $fillable = [
        'title',
        'description',
        'order_column',
    ];

    protected static function newFactory(): ForumFactory
    {
        return ForumFactory::new();
    }

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
        // TODO return true;
        return false;
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

    /**
     * @return BelongsTo<ForumCategory, Forum>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'forum_category_id');
    }

    /**
     * @return HasMany<ForumTopic>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class, 'forum_id', 'id');
    }

    /**
     * @return HasManyThrough<ForumTopicComment>
     */
    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(ForumTopicComment::class, ForumTopic::class, 'forum_id', 'forum_topic_id');
    }
}
