<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;

class Rating extends BaseModel
{
    // TODO rename Rating table to ratings
    // TODO rename RatingValue column to value
    // TODO rename Created to created_at
    // TODO rename Updated to updated_at
    // TODO drop User, RatingObjectType, RatingID, use ratable morph instead
    // TODO drop ratings_user_rating_unique index, use ratable morph instead
    protected $table = 'Rating';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'User',
        'RatingObjectType',
        'RatingID',
        'RatingValue',
    ];

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
