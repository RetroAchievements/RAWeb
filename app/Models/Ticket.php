<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\TicketResolution;
use App\Community\Enums\TicketSignalVerdict;
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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ticket extends BaseModel
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;
    use SoftDeletes;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected $table = 'tickets';

    /**
     * The audit log event written when the reporter is reminded to answer a request for more information.
     */
    public const REPORTER_REMINDED_EVENT = 'reporterReminded';

    /**
     * The audit log events written when a dev claims a ticket, releases their own claim,
     * is notified about an upcoming expiry, or allows their own claim to expire.
     */
    public const CLAIMED_EVENT = 'claimed';
    public const CLAIM_RELEASED_EVENT = 'claimReleased';
    public const CLAIM_EXPIRED_EVENT = 'claimExpired'; // the Server account is the causer
    public const CLAIM_EXPIRY_WARNED_EVENT = 'claimExpiryWarned';

    protected $fillable = [
        'ticketable_type',
        'ticketable_id',
        'assignee_id',
        'reporter_id',
        'type',
        'hardcore',
        'body',
    ];

    protected $casts = [
        'type' => TicketType::class,
        'state' => TicketState::class,
        'resolution' => TicketResolution::class,
        'current_signal_verdict' => TicketSignalVerdict::class, // only open tickets have one of these
        'resolved_signal_verdict' => TicketSignalVerdict::class, // the verdict a ticket had when it was resolved or closed - cleared on reopen
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

    /**
     * Only truthy when the ticket is assigned to a user other than the triggerable's maintainer.
     * Only assignments like these can actually expire.
     */
    public function isClaimed(): bool
    {
        if ($this->assignee_id === null || !$this->state->isOpen()) {
            return false;
        }

        $ticketableModel = $this->getTicketableModel();
        $maintainerUserId = $ticketableModel instanceof Achievement
            ? ($ticketableModel->activeMaintainer->user_id ?? $ticketableModel->user_id)
            : $ticketableModel->author_id;

        return $this->assignee_id !== $maintainerUserId;
    }

    public function canReporterRequestManualUnlock(): bool
    {
        if (
            $this->resolution !== TicketResolution::NetworkProblems
            || $this->ticketable_type !== TicketableType::Achievement->value
            || $this->reporter_id === null
        ) {
            return false;
        }

        $reporterAchievementUnlock = PlayerAchievement::query()
            ->where('user_id', $this->reporter_id)
            ->where('achievement_id', $this->ticketable_id)
            ->first();

        if ($reporterAchievementUnlock === null) {
            return true;
        }

        return $this->hardcore && $reporterAchievementUnlock->unlocked_hardcore_at === null;
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'state',
                'resolution',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    public function getIsOpenAttribute(): bool
    {
        return $this->state->isOpen();
    }

    public function getIsWaitingOnReporterAttribute(): bool
    {
        return
            $this->state === TicketState::Request
            || $this->resolution === TicketResolution::NoReplyFromReporter;
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
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<TicketComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'commentable_id')->where('commentable_type', CommentableType::Ticket);
    }

    /**
     * @return HasMany<TicketComment, $this>
     */
    public function discussionComments(): HasMany
    {
        return $this->comments()->notAutomated()->orderBy('created_at')->orderBy('id');
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
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id')->withTrashed();
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

    /**
     * @return BelongsTo<Trigger, $this>
     */
    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class, 'trigger_id', 'id')->withTrashed();
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
    public function scopeQuarantined(Builder $query): Builder
    {
        return $query->where('state', TicketState::Quarantined);
    }

    /**
     * Excludes tickets whose ticketable was deleted.
     *
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeWithLiveTicketable(Builder $query): Builder
    {
        return $query->whereHasMorph('ticketable', [Achievement::class, Leaderboard::class]);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeVisibleTo(Builder $query, ?User $user = null): Builder
    {
        // Guests can't see tickets.
        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query;
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
    public function scopeForSystem(Builder $query, int $systemId): Builder
    {
        return $query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $q) => $q->whereHas('game', fn (Builder $game) => $game->where('system_id', $systemId)),
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
        return $query->where('assignee_id', $user->id);
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeForMaintainer(Builder $query, User $user): Builder
    {
        return $query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $morphQuery, string $ticketableType) => match ($ticketableType) {
                Achievement::class => $morphQuery->where(fn (Builder $achievementQuery) => $achievementQuery
                    ->whereHas('activeMaintainer', fn (Builder $maintainerQuery) => $maintainerQuery->where('user_id', $user->id))
                    ->orWhere(fn (Builder $unmaintainedQuery) => $unmaintainedQuery
                        ->whereDoesntHave('activeMaintainer')
                        ->where('user_id', $user->id)
                    )
                ),
                Leaderboard::class => $morphQuery->where('author_id', $user->id),
                default => throw new LogicException("Unexpected ticketable type: {$ticketableType}"),
            },
        );
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeOnGamesSubscribedToBy(Builder $query, User $user): Builder
    {
        return $query->whereHasMorph(
            'ticketable',
            [Achievement::class, Leaderboard::class],
            fn (Builder $q) => $q->whereIn('game_id', Subscription::query()
                ->select('subject_id')
                ->where('subject_type', SubscriptionSubjectType::GameTickets)
                ->where('user_id', $user->id)
                ->where('state', true)),
        );
    }

    /**
     * @param Builder<Ticket> $query
     * @return Builder<Ticket>
     */
    public function scopeNotAssignedTo(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('assignee_id')
            ->orWhere('assignee_id', '!=', $user->id));
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
