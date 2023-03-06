<?php

declare(strict_types=1);

namespace LegacyApp\Community\Models;

use Database\Factories\Legacy\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Support\Database\Eloquent\BaseModel;

class Ticket extends BaseModel
{
    use HasFactory;

    protected $table = 'Ticket';

    public const CREATED_AT = 'ReportedAt';
    public const UPDATED_AT = 'Updated';

    protected $dates = [
        'ResolvedAt',
    ];

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'AchievementID');
    }
}
