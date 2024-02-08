<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;

class GameAlternative extends BaseModel
{
    // TODO drop GameAlternatives, migrate to game_sets
    protected $table = 'GameAlternatives';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    // == accessors

    protected $visible = [
        'gameID',
        'gameIDAlt',
    ];

    // == mutators

    // == relations

    // == scopes
}
