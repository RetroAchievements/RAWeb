<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Comment extends BaseModel
{
    use Searchable;
    use SoftDeletes;

    // TODO rename Comment table to comments
    // TODO rename ID column id
    // TODO rename UserID column to user_id
    // TODO drop ArticleType, migrate to commentable_type (morph map)
    // TODO drop ArticleID, migrate to commentable_id
    // TODO rename Payload to body or payload
    // TODO rename Submitted to created_at
    // TODO rename Edited to updated_at
    protected $table = 'Comment';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Submitted';
    public const UPDATED_AT = 'Edited';

    protected $fillable = [
        'Payload',
    ];

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'ID',
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
     * @throws Exception
     */
    private function notImplementedException(): void
    {
        throw new Exception('Use derived comment model class in the comments() morphTo() relationship instead of ' . Comment::class . '. Add link attribute getters to the derived class. Use A dedicated controller for it and use the prepared actions.');
    }

    // == mutators

    // == relations

    /**
     * @return MorphTo<Model, Comment>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, Comment>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'UserID')->withDefault(['username' => 'Deleted User']);
    }
}
