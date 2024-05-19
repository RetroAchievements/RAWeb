<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class ForumCategory extends BaseModel
{
    use Searchable;
    use SoftDeletes;

    // TODO rename ForumCategory table to forum_categories
    // TODO rename ID column to id, remove getIdAttribute()
    // TODO rename Name column to title, remove getTitleAttribute()
    // TODO rename Description column to description, remove getDescriptionAttribute()
    // TODO rename DisplayOrder column to order_column, remove getOrderColumnAttribute()
    // TODO rename Created column to created_at, remove getCreatedAtAttribute()
    // TODO rename Updated column to updated_at, remove getUpdatedAtAttribute()
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

    public function getCanonicalUrlAttribute(): string
    {
        return route('forum-category.show', [$this, $this->getSlugAttribute()]);
    }

    // TODO remove after Created renamed to created_at
    public function getCreatedAtAttribute(): ?Carbon
    {
        return $this->attributes['Created'] ? Carbon::parse($this->attributes['Created']) : Carbon::now();
    }

    // TODO remove after Description renamed to description
    public function getDescriptionAttribute(): string
    {
        return $this->attributes['Description'];
    }

    // TODO remove after ID renamed to id
    public function getIdAttribute(): int
    {
        return $this->attributes['ID'];
    }

    // TDOO remove after DisplayOrder renamed to order_column
    public function getOrderColumnAttribute(): int
    {
        return $this->attributes['DisplayOrder'];
    }

    public function getPermalinkAttribute(): string
    {
        return route('forum-category.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    // TODO remove after Name renamed to title
    public function getTitleAttribute(): string
    {
        return $this->Name;
    }

    // TODO remove after Updated renamed to updated_at
    public function getUpdatedAtAttribute(): Carbon
    {
        return $this->attributes['Updated'] ? Carbon::parse($this->attributes['Updated']) : Carbon::now();
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
