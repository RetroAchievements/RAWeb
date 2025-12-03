<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\SubscriptionSubjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDelayedSubscription extends Model
{
    protected $table = 'user_delayed_subscriptions';

    public const UPDATED_AT = null; // We only track created_at.

    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'first_update_id',
    ];

    protected $casts = [
        'subject_type' => SubscriptionSubjectType::class,
    ];

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

    // == helpers
}
