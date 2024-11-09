<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\HasAuthor;
use App\Community\Contracts\HasComments;
use App\Support\Database\Eloquent\BaseModel;
use Carbon\Carbon;
use Database\Factories\NewsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class News extends BaseModel implements HasComments, HasMedia
{
    use HasAuthor;
    /** @use HasFactory<NewsFactory> */
    use HasFactory;
    use InteractsWithMedia;

    use Searchable;
    use SoftDeletes;

    // TODO rename News table to news
    // TODO rename ID column to id
    // TODO rename Timestamp column to created_at
    // TODO rename Updated column to updated_at
    // TODO rename Title column to title
    // TODO rename Payload column to body
    // TODO drop Link, include in body/Payload
    // TODO drop Image, migrate to media
    protected $table = 'News';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Timestamp';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'Title',
        'lead',
        'Payload',
        'user_id',
        'Link',
        'Image',
        'publish_at',
        'unpublish_at',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'unpublish_at' => 'datetime',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'ID',
            'Title',
            'lead',
            // 'Payload',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO return $this->isPublished();
        return false;
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
                    ->fit(Fit::Max, 1000, 600);
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

    /**
     * @return MorphMany<NewsComment>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(NewsComment::class, 'commentable');
    }

    /**
     * @return BelongsTo<User, News>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes

    // == instance methods

    /**
     * TODO Migrate to Markdown. This is a temporary stopgap.
     * Corrects missing quotes in `href` attributes of anchor
     * tags so they don't break the site homepage.
     */
    public static function sanitizeMaybeInvalidHtml(string $maybeHtmlContent): string
    {
        // Fix missing quotes in href attributes
        $pattern = '/<a\s+href=([^\'" >]+)(.*?)>(.*?)<\/a>/i';
        $replacement = '<a href="$1"$2>$3</a>';
        $fixedHtml = preg_replace($pattern, $replacement, $maybeHtmlContent);

        // Allow only <a> and <br> tags
        $allowedTags = '<a><br>';
        $saferHtml = strip_tags($fixedHtml, $allowedTags);

        return $saferHtml;
    }
}
