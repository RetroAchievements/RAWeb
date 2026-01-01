<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\ModerationActionType;
use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModerationAction extends BaseModel
{
    protected $table = 'user_moderation_actions';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'actioned_by_id',
        'action',
        'reason',
        'expires_at',
    ];

    protected $casts = [
        'action' => ModerationActionType::class,
        'expires_at' => 'datetime',
    ];

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by_id', 'ID')->withTrashed();
    }

    // == scopes
}
