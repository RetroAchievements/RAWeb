<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\TicketState;
use App\Platform\Enums\AchievementFlag;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereIn('ReportState', [TicketState::Open, TicketState::Request]);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereIn('ReportState', [TicketState::Resolved, TicketState::Closed]);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForGame(Builder $query, Game $game): Builder
    {
        return $query->whereHas('achievement', function($query) use ($game) {
            $query->where('GameID', $game->id);
        });
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForDeveloper(Builder $query, User $developer): Builder
    {
        return $query->whereHas('achievement', function($query) use ($developer) {
            $query->where('user_id', $developer->id);
        });
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeOfficialCore(Builder $query): Builder
    {
        return $query->whereHas('achievement', function($query) {
            $query->where('Flags', AchievementFlag::OfficialCore);
        });
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeUnofficial(Builder $query): Builder
    {
        return $query->whereHas('achievement', function($query) {
            $query->where('Flags', AchievementFlag::Unofficial);
        });
    }
}
