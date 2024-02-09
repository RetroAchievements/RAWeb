<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;

class Vote extends BaseModel
{
    // TODO rename Votes table to votes
    // TODO rename Vote column to vote
    // TODO rename Created column to created_at
    // TODO rename Updated column to updated_at
    // TODO drop User, use user_id instead
    // TODO drop AchievementID, use votable morph instead
    // TODO drop votes_user_achievement_id_unique
    protected $table = 'Votes';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
