<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends BaseModel
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tickets';

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
        'type' => TicketType::class,
        'state' => TicketState::class,
        'resolved_at' => 'datetime',
    ];

    protected static function newFactory(): TicketFactory
    {
        return TicketFactory::new();
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
        return $this->belongsTo(User::class, 'ticketable_author_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolver_id')->withTrashed();
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
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereIn('state', [TicketState::Open, TicketState::Request]);
    }

    /**
     * Tickets that are actively awaiting the developer's action.
     * Excludes "Request" tickets which are reassigned back to the reporter.
     *
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeAwaitingDeveloper(Builder $query): Builder
    {
        return $query->where('state', TicketState::Open);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereIn('state', [TicketState::Resolved, TicketState::Closed]);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeQuarantined(Builder $query): Builder
    {
        return $query->where('state', TicketState::Quarantined);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForGame(Builder $query, Game $game): Builder
    {
        return $query->whereHas('achievement', function ($query) use ($game) {
            $query->where('game_id', $game->id);
        });
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForAchievement(Builder $query, Achievement $achievement): Builder
    {
        return $query->where('ticketable_id', $achievement->id)
            ->where('ticketable_type', 'achievement');
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForDeveloper(Builder $query, User $developer): Builder
    {
        return $query->where('ticketable_author_id', $developer->id);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeOfficialCore(Builder $query): Builder
    {
        return $query->whereHas('achievement', function ($query) {
            $query->where('is_promoted', true);
        });
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeUnofficial(Builder $query): Builder
    {
        return $query->whereHas('achievement', function ($query) {
            $query->where('is_promoted', false);
        });
    }
}
