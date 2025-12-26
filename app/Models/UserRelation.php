<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\UserRelationStatus;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\UserRelationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    // == scopes
}
