<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\UserRelationship;
use App\Support\Database\Eloquent\BaseModel;

class UserRelation extends BaseModel
{
    // TODO rename Friends table to user_relations
    // TODO migrate Friendship column to status
    protected $table = 'Friends';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'User',
        'Friend',
        'Friendship',
    ];

    // == accessors

    // == mutators

    // == relations

    public static function getRelationship(string $user, string $relatedUser): int
    {
        $relation = UserRelation::where('User', $user)
            ->where('Friend', $relatedUser)
            ->first();

        return $relation ? $relation->Friendship : UserRelationship::NotFollowing;
    }

    // == scopes
}
