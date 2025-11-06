<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// TODO: replace with Laravel standard features and/or Fortify
class PasswordResetToken extends BaseModel
{
    protected $table = 'password_reset_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'ip_address',
        'created_at',
    ];

    public $timestamps = false;

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
