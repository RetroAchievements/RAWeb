<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\TriggerTicketState;
use App\Community\Enums\TriggerTicketType;
use App\Platform\Enums\AchievementFlag;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\TriggerTicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TriggerTicket extends BaseModel
{
    /** @use HasFactory<TriggerTicketFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'trigger_tickets';

    protected $fillable = [
        'ticketable_type',
        'ticketable_id',
        'ticketable_author_id',
        'reporter_id',
        'type',
        'hardcore',
        'body',
    ];

    protected $casts = [
        'type' => TriggerTicketType::class,
        'state' => TriggerTicketState::class,
        'resolved_at' => 'datetime',
    ];

    protected static function newFactory(): TriggerTicketFactory
    {
        return TriggerTicketFactory::new();
    }

    // == accessors

    public function getIsOpenAttribute(): bool
    {
        return $this->state->isOpen();
    }

    // == mutators

    // == relations

    /**
     * @return MorphTo<Model, $this>
     */
    public function ticketable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Achievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'ticketable_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ticketable_author_id', 'ID')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id', 'ID')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolver_id', 'ID')->withTrashed();
    }

    /**
     * @return BelongsTo<Emulator, $this>
     */
    public function emulator(): BelongsTo
    {
        return $this->belongsTo(Emulator::class, 'emulator_id', 'id');
    }

    /**
     * @return BelongsTo<GameHash, $this>
     */
    public function gameHash(): BelongsTo
    {
        return $this->belongsTo(GameHash::class, 'game_hash_id', 'id');
    }

    // == scopes

    /**
     * @param Builder<TriggerTicket> $query
     * @return Builder<TriggerTicket>
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereIn('state', [TriggerTicketState::Open, TriggerTicketState::Request]);
    }

    /**
     * Tickets that are actively awaiting the developer's action.
     * Excludes "Request" tickets which are reassigned back to the reporter.
     *
     * @param Builder<TriggerTicket> $query
     * @return Builder<TriggerTicket>
     */
    public function scopeAwaitingDeveloper(Builder $query): Builder
    {
        return $query->where('state', TriggerTicketState::Open);
    }

    /**
     * @param Builder<TriggerTicket> $query
     * @return Builder<TriggerTicket>
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereIn('state', [TriggerTicketState::Resolved, TriggerTicketState::Closed]);
    }

    /**
     * @param Builder<TriggerTicket> $query
     * @return Builder<TriggerTicket>
     */
    public function scopeForGame(Builder $query, Game $game): Builder
    {
        return $query->whereHas('achievement', function ($query) use ($game) {
            $query->where('GameID', $game->id);
        });
    }

    /**
     * @param Builder<TriggerTicket> $query
     * @return Builder<TriggerTicket>
     */
    public function scopeForAchievement(Builder $query, Achievement $achievement): Builder
    {
        return $query->where('ticketable_id', $achievement->id)
            ->where('ticketable_type', 'achievement');
    }

    /**
     * @param Builder<TriggerTicket> $query
     * @return Builder<TriggerTicket>
     */
    public function scopeForDeveloper(Builder $query, User $developer): Builder
    {
        return $query->where('ticketable_author_id', $developer->id);
    }

    /**
     * @param Builder<TriggerTicket> $query
     * @return Builder<TriggerTicket>
     */
    public function scopeOfficialCore(Builder $query): Builder
    {
        return $query->whereHas('achievement', function ($query) {
            $query->where('Flags', AchievementFlag::OfficialCore->value);
        });
    }

    /**
     * @param Builder<TriggerTicket> $query
     * @return Builder<TriggerTicket>
     */
    public function scopeUnofficial(Builder $query): Builder
    {
        return $query->whereHas('achievement', function ($query) {
            $query->where('Flags', AchievementFlag::Unofficial->value);
        });
    }
}
