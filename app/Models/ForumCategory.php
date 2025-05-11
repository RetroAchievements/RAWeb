<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use App\Support\Routing\HasSelfHealingUrls;
use Database\Factories\ForumCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForumCategory extends BaseModel
{
    /** @use HasFactory<ForumCategoryFactory> */
    use HasFactory;
    use HasSelfHealingUrls;
    use SoftDeletes;

    protected $table = 'forum_categories';

    protected $fillable = [
        'title',
        'description',
        'order_column',
    ];

    protected static function newFactory(): ForumCategoryFactory
    {
        return ForumCategoryFactory::new();
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
