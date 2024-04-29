<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class ForumCategory extends BaseModel
{
    use Searchable;
    use SoftDeletes;

    // TODO rename ForumCategory table to forum_categories
    // TODO rename ID column to id, remove getIdAttribute()
    // TODO rename Name column to title
    // TODO rename Description column to description
    // TODO rename DisplayOrder column to order_column
    // TODO rename Created column to created_at
    // TODO rename Updated column to updated_at
    protected $table = 'ForumCategory';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'Name',
        'Description',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'ID',
            'Name',
            'Description',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO return true;
        return false;
    }

    // == accessors

    // TODO remove after rename
    public function getIdAttribute(): int
    {
        return $this->attributes['ID'];
    }

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

    public function getTitleAttribute(): string
    {
        return $this->Name;
    }

    // == mutators

    // == relations

    /**
     * @return HasMany<Forum>
     */
    public function forums(): HasMany
    {
        return $this->hasMany(Forum::class);
    }

    // == scopes
}
