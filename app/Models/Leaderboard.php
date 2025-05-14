<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Enums\ArticleType;
use App\Platform\Actions\RecalculateLeaderboardTopEntryAction;
use App\Platform\Contracts\HasVersionedTrigger;
use App\Platform\Enums\ValueFormat;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\LeaderboardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

// TODO implements HasComments

/**
 * @implements HasVersionedTrigger<Leaderboard>
 */
class Leaderboard extends BaseModel implements HasVersionedTrigger
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

    // TODO rename LeaderboardDef table to leaderboards
    // TODO rename ID column to id
    // TODO rename GameID column to game_id
    // TODO rename Format column to format, remove getFormatAttribute()
    // TODO rename Title column to title, remove getTitleAttribute()
    // TODO rename Description column to description, remove getDescriptionAttribute()
    // TODO rename LowerIsBetter column to rank_asc, remove getRankAscAttribute()
    // TODO rename DisplayOrder column to order_column, remove getOrderColumnAttribute()
    // TODO rename Created column to created_at, set to non-nullable, remove getCreatedAtAttribute()
    // TODO rename Updated column to updated_at, set to non-nullable, remove getUpdatedAtAttribute()
    // TODO drop Mem, migrate to triggerable morph
    // TODO drop author_id, migrate to triggerable morph author
    protected $table = 'LeaderboardDef';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected $fillable = [
        'Title',
        'Description',
        'Format',
        'LowerIsBetter',
        'DisplayOrder',
        'trigger_id',
    ];

    protected static function newFactory(): LeaderboardFactory
    {
        return LeaderboardFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        // When the LowerIsBetter flag changes, clear the top entry.
        static::updating(function (Leaderboard $leaderboard) {
            if ($leaderboard->isDirty('LowerIsBetter')) {
                $leaderboard->top_entry_id = null;
            }
        });

        // After the update is complete, recalculate the top entry if LowerIsBetter changed.
        static::updated(function (Leaderboard $leaderboard) {
            if ($leaderboard->wasChanged('LowerIsBetter')) {
                (new RecalculateLeaderboardTopEntryAction())->execute($leaderboard);
            }
        });
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'Title',
                'Description',
                'Format',
                'LowerIsBetter',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    // TODO remove after rename
    public function getCreatedAtAttribute(): Carbon
    {
        return $this->attributes['Created'] ? Carbon::parse($this->attributes['Created']) : Carbon::now();
    }

    // TODO remove after rename
    public function getIdAttribute(): int
    {
        return $this->attributes['ID'];
    }

    public function getCanonicalUrlAttribute(): string
    {
        return route('leaderboard.show', [$this, $this->getSlugAttribute()]);
    }

    // TODO remove after rename
    public function getDescriptionAttribute(): string
    {
        return $this->attributes['Description'] ?? '';
    }

    // TODO remove after rename
    public function getFormatAttribute(): ?string
    {
        return $this->attributes['Format'] ?? null;
    }

    // TODO remove after rename
    public function getOrderColumnAttribute(): int
    {
        return $this->attributes['DisplayOrder'];
    }

    public function getPermalinkAttribute(): string
    {
        return route('leaderboard.show', $this);
    }

    // TODO remove after rename
    public function getRankAscAttribute(): bool
    {
        return $this->attributes['LowerIsBetter'] === 1;
    }

    public function getSlugAttribute(): string
    {
        return $this->title ? '-' . Str::slug($this->title) : '';
    }

    // TODO remove after rename
    public function getTitleAttribute(): string
    {
        return $this->attributes['Title'] ?? '';
    }

    // TODO remove after rename
    public function getUpdatedAtAttribute(): Carbon
    {
        return $this->attributes['Updated'] ? Carbon::parse($this->attributes['Updated']) : Carbon::now();
    }

    // == mutators

    // == relations

    /**
     * @return BelongsTo<User, Leaderboard>
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'ID')->withTrashed();
    }

    /**
     * @return HasMany<LeaderboardEntry>
     */
    public function entries(bool $includeUnrankedUsers = false): HasMany
    {
        return $this->hasMany(LeaderboardEntry::class, 'leaderboard_id')
            ->whereHas('user', function ($query) use ($includeUnrankedUsers) {
                if (!$includeUnrankedUsers) {
                    $query->where('Untracked', '!=', 1)
                        ->whereNull('unranked_at');
                }
            });
    }

    /**
     * @return HasMany<LeaderboardEntry>
     */
    public function sortedEntries(): HasMany
    {
        $entries = $this->entries();

        $direction = $this->LowerIsBetter ? 'ASC' : 'DESC';

        if ($this->Format === ValueFormat::ValueUnsigned) {
            $entries->orderByRaw(toUnsignedStatement('score') . ' ' . $direction);
        } else {
            $entries->orderBy('score', $direction);
        }

        $entries->orderBy('updated_at');

        return $entries;
    }

    /**
     * @return BelongsTo<Game, Leaderboard>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'GameID', 'ID');
    }

    /**
     * @return BelongsTo<LeaderboardEntry, Leaderboard>
     */
    public function topEntry(): BelongsTo
    {
        return $this->belongsTo(LeaderboardEntry::class, 'top_entry_id');
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'ArticleID')->where('ArticleType', ArticleType::Leaderboard);
    }

    /**
     * TODO use HasComments / polymorphic relationship
     *
     * @return HasMany<Comment>
     */
    public function visibleComments(?User $user = null): HasMany
    {
        /** @var ?User $user */
        $currentUser = $user ?? Auth::user();

        return $this->comments()->visibleTo($currentUser);
    }

    /**
     * @return BelongsTo<Trigger, Leaderboard>
     */
    public function currentTrigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class, 'trigger_id', 'ID');
    }

    public function trigger(): MorphOne
    {
        return $this->morphOne(Trigger::class, 'triggerable')
            ->latest('version');
    }

    public function triggers(): MorphMany
    {
        return $this->morphMany(Trigger::class, 'triggerable')
            ->orderBy('version');
    }

    // == scopes

    /**
     * @param Builder<Leaderboard> $query
     * @return Builder<Leaderboard>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('DisplayOrder', '>=', 0);
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

    // == helpers

    public function submitValidationHash(User $user, int $score, int $offset = 0): string
    {
        $data = $this->id . $user->username . $score;
        if ($offset > 0) {
            $data .= $offset;
        }

        return md5($data);
    }

    public function getRank(int $score): int
    {
        $entries = $this->entries();

        if ($this->LowerIsBetter) {
            if ($this->Format === ValueFormat::ValueUnsigned) {
                $entries->whereRaw(toUnsignedStatement('score') . ' < ' . toUnsignedStatement(strval($score)));
            } else {
                $entries->where('score', '<', $score);
            }

            return $entries->count() + 1;
        }

        // have to use <= for reverse sort so the number of users being subtracted includes
        // all users with the same score (see issue #1201)
        $numEntries = $entries->count();

        if ($this->Format === ValueFormat::ValueUnsigned) {
            $entries->whereRaw(toUnsignedStatement('score') . ' <= ' . toUnsignedStatement(strval($score)));
        } else {
            $entries->where('score', '<=', $score);
        }

        return $numEntries - $entries->count() + 1;
    }

    public function isBetterScore(int $score, int $existingScore): bool
    {
        if ($this->Format === ValueFormat::ValueUnsigned) {
            if ($score < 0 && $existingScore >= 0) {
                return $this->LowerIsBetter ? false : true; // negative score is a very high value when unsigned
            } elseif ($existingScore < 0 && $score >= 0) {
                return $this->LowerIsBetter ? true : false; // negative existing score is a very high value when unsigned
            }
            // values have same sign, just compare them normally
        }

        if ($this->LowerIsBetter) {
            return $score < $existingScore;
        } else {
            return $score > $existingScore;
        }
    }
}
