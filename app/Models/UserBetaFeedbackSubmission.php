<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBetaFeedbackSubmission extends BaseModel
{
    protected $table = 'user_beta_feedback_submissions';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'beta_name',
        'rating',
        'positive_feedback',
        'negative_feedback',
        'page_url',
        'user_agent',
        'visit_count',
        'first_visited_at',
        'last_visited_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'visit_count' => 'integer',
        'first_visited_at' => 'datetime',
        'last_visited_at' => 'datetime',
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
}
