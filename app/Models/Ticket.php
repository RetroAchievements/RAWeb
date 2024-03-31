<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Ticket extends BaseModel
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    // TODO rename Ticket table to tickets
    // TODO rename ID column to id
    // TODO rename ReportType column to type
    // TODO rename ReportNotes column to body
    // TODO rename ReportedAt column to created_at
    // TODO rename ResolvedAt column to resolved_at
    // TODO rename ReportState column to state
    // TODO rename Updated column to updated_at
    // TODO drop AchievementID, use ticketable morph instead
    // TODO drop Hardcore, derived from player_session
    protected $table = 'Ticket';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'ReportedAt';
    public const UPDATED_AT = 'Updated';

    protected $casts = [
        'ResolvedAt' => 'datetime',
    ];

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
    }

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'ID',
            'ReportNotes',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO return true;
        return false;
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return BelongsTo<Achievement, Ticket>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'AchievementID');
    }

    /**
     * @return BelongsTo<User, Ticket>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id', 'ID');
    }

    /**
     * @return BelongsTo<User, Ticket>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolver_id', 'ID');
    }

    // == scopes
}
