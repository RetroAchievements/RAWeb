<?php

declare(strict_types=1);

namespace App\Community\Models;

use App\Support\Database\Eloquent\BaseModel;
use App\Support\Shortcode\HasShortcodeFields;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Comment extends BaseModel
{
    use HasShortcodeFields;
    use Searchable;
    use SoftDeletes;

    protected $table = 'comments';

    protected $fillable = [
        'body',
    ];

    protected $with = [
        'commentable',
        'user',
    ];

    /**
     * @see HasShortcodeFields
     */
    protected array $shortcodeFields = [
        'body',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            // 'body', // TODO: doable? might be a bit extreme with some of those posts...
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        /*
         * TODO: which comments should be indexed?
         */
        return false;
    }

    // == accessors

    public function getEditLinkAttribute(): string
    {
        $this->notImplementedException();

        return '';
    }

    public function getPermalinkAttribute(): string
    {
        $this->notImplementedException();

        return '';
    }

    /**
     * @throws \Exception
     */
    private function notImplementedException(): void
    {
        throw new \Exception('Use derived comment model class in the comments() morphTo() relationship instead of ' . Comment::class . '. Add link attribute getters to the derived class. Use A dedicated controller for it and use the prepared actions.');
    }

    // == mutators

    // == relations

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Site\Models\User::class)->withDefault(['username' => 'Deleted User']);
    }
}
