<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Enums\TicketableType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

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

    // == helpers

    public function getTicketableModel(): Achievement|Leaderboard
    {
        $ticketable = $this->ticketable;
        if ($ticketable instanceof Achievement || $ticketable instanceof Leaderboard) {
            return $ticketable;
        }

        throw new LogicException("Ticket {$this->id} has no resolvable ticketable (type: {$this->ticketable_type}).");
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
     * Unsafe without a `ticketable_type = achievement` filter.
     * Use `getTicketableModel()`, `ticketable()`, or pair with `scopeForTicketableType`.
     *
     * @return BelongsTo<Achievement, $this>
     */
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class, 'ticketable_id');
    }

    /**
     * Unsafe without a `ticketable_type = leaderboard` filter.
     * Use `getTicketableModel()`, `ticketable()`, or pair with `scopeForTicketableType`.
     *
     * @return BelongsTo<Leaderboard, $this>
     */
    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(Leaderboard::class, 'ticketable_id');
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
    public function scopeOpen(Builder $query): Builder
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
    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can('manage', self::class)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('state', '!=', TicketState::Quarantined->value)
                ->orWhere('reporter_id', $user->id);
        });
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForGame(Builder $query, Game $game): Builder
    {
        return $query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $q) => $q->where('game_id', $game->id),
        );
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForAchievement(Builder $query, Achievement $achievement): Builder
    {
        return $query->where('ticketable_id', $achievement->id)
            ->where('ticketable_type', TicketableType::Achievement->value);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForAssignee(Builder $query, User $user): Builder
    {
        return $query->where('ticketable_author_id', $user->id);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForTicketableType(Builder $query, TicketableType $type): Builder
    {
        return $query->where('ticketable_type', $type->value);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopePromoted(Builder $query): Builder
    {
        return $query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $q, string $type) => match ($type) {
                Achievement::class => $q->where('is_promoted', true),
                Leaderboard::class => $q->where('state', '!=', LeaderboardState::Unpromoted->value),
                default => throw new LogicException("Unexpected ticketable type: {$type}"),
            },
        );
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeUnpromoted(Builder $query): Builder
    {
        return $query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $q, string $type) => match ($type) {
                Achievement::class => $q->where('is_promoted', false),
                Leaderboard::class => $q->where('state', LeaderboardState::Unpromoted->value),
                default => throw new LogicException("Unexpected ticketable type: {$type}"),
            },
        );
    }
}
