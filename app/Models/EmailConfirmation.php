<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// TODO drop User
class EmailConfirmation extends BaseModel
{
    protected $table = 'EmailConfirmations';

    protected $fillable = [
        'User',
        'EmailCookie',
        'Expires',
        'user_id',
    ];

    public $timestamps = false;

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, EmailConfirmation>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
