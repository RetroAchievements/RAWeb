<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\SubscriptionSubjectType;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends BaseModel
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'user_id',
        'state',
    ];

    protected $casts = [
        'subject_type' => SubscriptionSubjectType::class,
        'state' => 'boolean',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, Subscription>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
