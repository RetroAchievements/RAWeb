<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Community\Concerns\HasAuthor;
use App\Community\Contracts\HasComments;
use App\Support\Database\Eloquent\BaseModel;
use App\Support\Shortcode\HasShortcodeFields;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class News extends BaseModel implements HasComments, HasMedia
{
    use HasAuthor;
    use HasFactory;
    use HasShortcodeFields;
    use InteractsWithMedia;
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'body',
        'lead',
        'title',
        'publish_at',
        'unpublish_at',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'unpublish_at' => 'datetime',
    ];

    protected $with = [
        'user',
        'media',
    ];

    /**
     * @see HasShortcodeFields
     */
    protected array $shortcodeFields = [
        'lead',
        'body',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            // 'body',
            'lead',
            'title',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        return $this->isPublished();
    }

    // == media

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->useFallbackUrl(asset('assets/images/news/image.webp'))
            // ->useFallbackPath(public_path('/assets/images/user/avatar.webp'))
            ->singleFile()
            // ->onlyKeepLatest(3)
            // ->acceptsFile(function (File $file) {
            //     return $file->mimeType === 'image/jpeg';
            // })
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('2xl')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Manipulations::FIT_MAX, 1000, 600);
            });
    }

    // == actions

    public function isPublished(): bool
    {
        if ($this->publish_at && $this->unpublish_at) {
            return Carbon::now()->between($this->publish_at, $this->unpublish_at);
        }
        if ($this->publish_at) {
            return Carbon::now()->isAfter($this->publish_at);
        }

        return false;
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('news.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('news.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    // == mutators

    // == relations

    public function comments(): MorphMany
    {
        return $this->morphMany(NewsComment::class, 'commentable');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Site\Models\User::class);
    }

    // == scopes
}
