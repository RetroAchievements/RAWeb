<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\UserRelationStatus;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\UserRelationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRelation extends BaseModel
{
    /** @use HasFactory<UserRelationFactory> */
    use HasFactory;

    protected $table = 'user_relations';

    protected $fillable = [
        'user_id',
        'related_user_id',
        'status',
    ];

    protected $casts = [
        'status' => UserRelationStatus::class,
    ];

    protected static function newFactory(): UserRelationFactory
    {
        return UserRelationFactory::new();
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * The user on the "source" side of the relation (the follower in a Following row).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * The user on the "target" side of the relation (the followee in a Following row).
     *
     * @return BelongsTo<User, $this>
     */
    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id', 'id');
    }

    // == scopes
}
