<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\CommentableType;
use App\Platform\Actions\RecalculateLeaderboardTopEntryAction;
use App\Platform\Contracts\HasPermalink;
use App\Platform\Contracts\HasVersionedTrigger;
use App\Platform\Contracts\Ticketable;
use App\Platform\Enums\LeaderboardState;
use App\Platform\Enums\TicketableType;
use App\Platform\Enums\ValueFormat;
use App\Platform\Services\GameOpenTicketCountService;
use App\Support\Database\Eloquent\BaseModel;
use Carbon\CarbonInterface;
use Database\Factories\LeaderboardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

// TODO implements HasComments

/**
 * @implements HasVersionedTrigger<Leaderboard>
 */
class Leaderboard extends BaseModel implements HasPermalink, HasVersionedTrigger, Ticketable
{
    /*
     * Shared Traits
     */
    /** @use HasFactory<LeaderboardFactory> */
    use HasFactory;

    use SoftDeletes;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    // TODO drop game_id, migrate to achievement_set_leaderboards, remove getGamesAttribute() in favor of true relationship
    protected $table = 'leaderboards';

    protected $fillable = [
        'title',
        'description',
        'format',
        'rank_asc',
        'order_column',
        'trigger_id',
        'state',
        'game_id',
        'trigger_definition',
        'author_id',
    ];

    protected $casts = [
        'game_id' => 'integer',
        'rank_asc' => 'boolean',
        'state' => LeaderboardState::class,
    ];

    protected static function newFactory(): LeaderboardFactory
    {
        return LeaderboardFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        // When the rank_asc flag changes, clear the top entry.
        static::updating(function (Leaderboard $leaderboard) {
            if ($leaderboard->isDirty('rank_asc')) {
                $leaderboard->top_entry_id = null;
            }
        });

        // After the update is complete, recalculate the top entry if rank_asc changed.
        static::updated(function (Leaderboard $leaderboard) {
            if ($leaderboard->wasChanged('rank_asc')) {
                (new RecalculateLeaderboardTopEntryAction())->execute($leaderboard->id);
            }

            if ($leaderboard->wasChanged(['game_id', 'state'])) {
                $service = app(GameOpenTicketCountService::class);
                $service->clearForGameId((int) $leaderboard->game_id);
                $originalGameId = $leaderboard->getOriginal('game_id');
                if ($leaderboard->wasChanged('game_id') && $originalGameId !== null) {
                    $service->clearForGameId((int) $originalGameId);
                }
            }
        });
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'description',
                'format',
                'rank_asc',
                'state',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == ticketable

    public function getTicketableType(): TicketableType
    {
        return TicketableType::Leaderboard;
    }

    public function getTicketableGame(): Game
    {
        return $this->game;
    }

    public function getTicketableGameId(): int
    {
        return $this->game_id;
    }

    public function getTicketableAssignee(?CarbonInterface $at = null): ?User
    {
        // leaderboards don't have a "maintainer" concept - the assignee is always the author.
        return $this->developer;
    }

    public function getTicketableTitle(): string
    {
        return $this->title;
    }

    public function getTicketableUrl(): string
    {
        return $this->getCanonicalUrlAttribute();
    }

    public function getTicketableIconUrl(): string
    {
        // Leaderboards don't have a dedicated badge, so display the game's icon.
        return media_asset($this->game->image_icon_asset_path);
    }

    public function getTicketableBadgeUrl(): ?string
    {
        return null;
    }

    public function demoteForTicket(User $byUser): void
    {
        if ($this->state === LeaderboardState::Unpromoted) {
            return;
        }

        $this->state = LeaderboardState::Unpromoted;
        $this->save();

        addArticleComment(
            'Server',
            CommentableType::Leaderboard,
            $this->id,
            "{$byUser->display_name} demoted this leaderboard to Unpromoted.",
            $byUser->display_name,
        );
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('leaderboard.show', [$this, $this->getSlugAttribute()]);
    }

    /**
     * Decompose `trigger_definition` into its four sections.
     * A leaderboard trigger string looks like:
     *  `STA:<start>::CAN:<cancel>::SUB:<submit>::VAL:<value>`.
     * This returns those sections keyed by lowercase name.
     *
     * @return array{start: string, cancel: string, submit: string, value: string}
     */
    public function getTriggerPartsAttribute(): array
    {
        $parts = ['start' => '', 'cancel' => '', 'submit' => '', 'value' => ''];
        $map = ['STA:' => 'start', 'CAN:' => 'cancel', 'SUB:' => 'submit', 'VAL:' => 'value'];

        foreach (explode('::', $this->trigger_definition) as $chunk) {
            $prefix = substr($chunk, 0, 4);
            if (isset($map[$prefix])) {
                $parts[$map[$prefix]] = substr($chunk, 4);
            }
        }

        return $parts;
    }

    /**
     * Get the games associated with this leaderboard.
     * TODO replace with proper relationship through achievement_set_leaderboards
     *
     * @return Collection<int, Game>
     */
    public function getGamesAttribute(): Collection
    {
        $game = $this->game;

        return $game ? collect([$game]) : collect();
    }

    public function getPermalinkAttribute(): string
    {
        return route('leaderboard.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, $this>
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }

    /**
     * @return HasMany<LeaderboardEntry, $this>
     */
    public function entries(bool $includeUnrankedUsers = false): HasMany
    {
        return $this->hasMany(LeaderboardEntry::class, 'leaderboard_id')
            ->whereHas('user', function ($query) use ($includeUnrankedUsers) {
                if (!$includeUnrankedUsers) {
                    $query->whereNull('unranked_at');
                }
            });
    }

    /**
     * @return HasMany<LeaderboardEntry, $this>
     */
    public function sortedEntries(): HasMany
    {
        $entries = $this->entries();

        $direction = $this->rank_asc ? 'asc' : 'desc';

        if ($this->format === ValueFormat::ValueUnsigned) {
            $entries->orderByRaw(toUnsignedStatement('score') . ' ' . $direction);
        } else {
            $entries->orderBy('score', $direction);
        }

        $entries->orderBy('updated_at');

        return $entries;
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id', 'id');
    }

    /**
     * @return BelongsTo<LeaderboardEntry, $this>
     */
    public function topEntry(): BelongsTo
    {
        return $this->belongsTo(LeaderboardEntry::class, 'top_entry_id');
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'commentable_id')->where('commentable_type', CommentableType::Leaderboard);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment, $this>
     */
    public function visibleComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->comments()->visibleTo($currentUser);
    }

    /**
     * @return BelongsTo<Trigger, $this>
     */
    public function currentTrigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class, 'trigger_id', 'id');
    }

    /**
     * @return MorphOne<Trigger, $this>
     */
    public function trigger(): MorphOne
    {
        return $this->morphOne(Trigger::class, 'triggerable')
            ->latest('version');
    }

    /**
     * @return MorphMany<Trigger, $this>
     */
    public function triggers(): MorphMany
    {
        return $this->morphMany(Trigger::class, 'triggerable')
            ->orderBy('version');
    }

    /**
     * @return MorphMany<Ticket, $this>
     */
    public function tickets(): MorphMany
    {
        return $this->morphMany(Ticket::class, 'ticketable');
    }

    // == scopes

    /**
     * @param Builder<Leaderboard> $query
     * @return Builder<Leaderboard>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('order_column', '>=', 0);
    }

    /**
     * @param Builder<Leaderboard> $query
     * @return Builder<Leaderboard>
     */
    public function scopePromoted(Builder $query): Builder
    {
        return $query->where('state', '!=', LeaderboardState::Unpromoted->value);
    }

    /**
     * @param Builder<Leaderboard> $query
     * @return Builder<Leaderboard>
     */
    public function scopeUnpromoted(Builder $query): Builder
    {
        return $query->where('state', LeaderboardState::Unpromoted->value);
    }

    /**
     * @param Builder<Leaderboard> $query
     * @return Builder<Leaderboard>
     */
    public function scopeWithTopEntry(Builder $query): Builder
    {
        return $query->with(['topEntry' => function ($q) {
            $q->with('user');
        }]);
    }

    /**
     * Filter by leaderboard state: 'active', 'disabled', 'unpromoted', 'all', or comma-separated values.
     *
     * @param Builder<Leaderboard> $query
     * @return Builder<Leaderboard>
     */
    public function scopeWithState(Builder $query, string $value): Builder
    {
        if ($value === 'all') {
            return $query;
        }

        $states = array_map('trim', explode(',', $value));

        return $query->whereIn('state', $states);
    }

    // == helpers

    public function getRank(int $score): int
    {
        $entries = $this->entries();

        if ($this->rank_asc) {
            if ($this->format === ValueFormat::ValueUnsigned) {
                $entries->whereRaw(toUnsignedStatement('score') . ' < ' . toUnsignedStatement(strval($score)));
            } else {
                $entries->where('score', '<', $score);
            }

            return $entries->count() + 1;
        }

        // Have to use <= for reverse sort so the number of users being subtracted includes
        // all users with the same score (see issue #1201).
        $numEntries = $entries->count();

        if ($this->format === ValueFormat::ValueUnsigned) {
            $entries->whereRaw(toUnsignedStatement('score') . ' <= ' . toUnsignedStatement(strval($score)));
        } else {
            $entries->where('score', '<=', $score);
        }

        return $numEntries - $entries->count() + 1;
    }

    public function isBetterScore(int $score, int $existingScore): bool
    {
        if ($this->format === ValueFormat::ValueUnsigned) {
            if ($score < 0 && $existingScore >= 0) {
                // A negative score is a very high value when unsigned.
                return $this->rank_asc ? false : true;
            } elseif ($existingScore < 0 && $score >= 0) {
                // A negative existing score is a very high value when unsigned.
                return $this->rank_asc ? true : false;
            }
            // Values have same sign, just compare them normally.
        }

        if ($this->rank_asc) {
            return $score < $existingScore;
        }

        return $score > $existingScore;
    }
}
