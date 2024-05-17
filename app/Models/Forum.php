<?php

declare(strict_types=1);

namespace App\Models;

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

    // TODO rename Forum table to forums
    // TODO rename ID column to id, remove getIdAttribute()
    // TODO rename CategoryID to forum_category_id
    // TODO rename Title to title, remove getTitleAttribute()
    // TODO rename Description to description, remove getDescriptionAttribute()
    // TODO rename DisplayOrder to order_column
    // TODO rename Created to created_at
    // TODO rename Updated to updated_at
    // TODO drop LatestCommentID -> derived
    protected $table = 'Forum';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'Title',
        'Description',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'ID',
            'Title',
            'Description',
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

    // TODO remove after rename
    public function getDescriptionAttribute(): string
    {
        return $this->attributes['Description'];
    }

    // TODO remove after rename
    public function getIdAttribute(): int
    {
        return $this->attributes['ID'];
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

    // TODO remove after rename
    public function getTitleAttribute(): string
    {
        return $this->attributes['Title'];
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<ForumCategory, Forum>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'CategoryID', 'ID');
    }

    /**
     * @return HasMany<ForumTopic>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class);
    }

    /**
     * @return HasManyThrough<ForumTopicComment>
     */
    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(ForumTopicComment::class, ForumTopic::class, 'ForumID', 'ForumTopicID');
    }
}
