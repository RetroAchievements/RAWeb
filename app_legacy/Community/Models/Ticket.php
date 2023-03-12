<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class Ticket extends BaseModel
{
    protected $table = 'Ticket';

    /**
     * @return BelongsTo<Achievement, Ticket>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'AchievementID');
    }
}
