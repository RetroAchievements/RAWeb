<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsersOnlineCount extends Model
{
    protected $table = 'users_online_counts';

    public const UPDATED_AT = null;

    protected $fillable = [
        'online_count',
    ];

    // == accessors

    // == mutators

    // == relations

    // == scopes
}
