<?php

declare(strict_types=1);

namespace App\Platform\Models;

use App\Support\Database\Eloquent\BaseModel;

class GameRelation extends BaseModel
{
    // TODO drop GameAlternatives, migrate to game_sets
    protected $table = 'GameAlternatives';

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
