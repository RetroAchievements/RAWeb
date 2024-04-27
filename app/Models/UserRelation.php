<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;

class UserRelation extends BaseModel
{
    // TODO rename Friends table to user_relations
    // TODO migrate Friendship column to status, remove getStatusAttribute()
    protected $table = 'Friends';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'user_id',
        'related_user_id',
        'User',
        'Friend',
        'Friendship',
    ];

    // == accessors

    // TODO remove after rename
    public function getStatusAttribute(): string
    {
        return $this->attributes['Friendship'];
    }

    // == mutators

    // == relations

    // == scopes
}
