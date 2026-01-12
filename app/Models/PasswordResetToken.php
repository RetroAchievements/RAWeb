<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

// TODO: replace with Laravel standard features and/or Fortify
class PasswordResetToken extends BaseModel
{
    use MassPrunable;

    protected $table = 'password_reset_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'ip_address',
        'created_at',
    ];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * @return Builder<PasswordResetToken>
     */
    public function prunable(): Builder
    {
        return $this->where('created_at', '<=', now()->subDays(5));
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

    // == helpers

    public static function isValidForUser(User $user, string $token): bool
    {
        $passwordResetToken = PasswordResetToken::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>', Carbon::now()->subMinutes(60))
            ->whereNotNull('token')
            ->first();

        return $passwordResetToken && Hash::check($token, $passwordResetToken->token);
    }
}
