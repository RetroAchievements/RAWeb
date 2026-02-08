<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailConfirmation extends BaseModel
{
    use MassPrunable;

    protected $table = 'email_confirmations';

    protected $fillable = [
        'user_id',
        'email_cookie',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * @return Builder<EmailConfirmation>
     */
    public function prunable(): Builder
    {
        return $this->where('expires_at', '<=', now());
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // == scopes
}
