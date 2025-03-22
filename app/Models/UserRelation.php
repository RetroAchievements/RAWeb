<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\UserRelationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserRelation extends BaseModel
{
    /** @use HasFactory<UserRelationFactory> */
    use HasFactory;

    // TODO rename Friends table to user_relations
    // TODO migrate Friendship column to status, remove getStatusAttribute()
    protected $table = 'Friends';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'user_id',
        'related_user_id',
        'Friendship',
    ];

    protected static function newFactory(): UserRelationFactory
    {
        return UserRelationFactory::new();
    }

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
