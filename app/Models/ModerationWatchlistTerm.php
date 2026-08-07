<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Cache\CacheKey;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\ModerationWatchlistTermFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ModerationWatchlistTerm extends BaseModel
{
    /** @use HasFactory<ModerationWatchlistTermFactory> */
    use HasFactory;
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    public const MINIMUM_TERM_LENGTH = 4;

    protected $table = 'moderation_watchlist_terms';

    protected $fillable = [
        'term',
        'note',
    ];

    protected static function newFactory(): ModerationWatchlistTermFactory
    {
        return ModerationWatchlistTermFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(function (self $watchlistTerm): void {
            if (mb_strlen((string) $watchlistTerm->term) < self::MINIMUM_TERM_LENGTH) {
                throw new InvalidArgumentException(sprintf(
                    'A watched term must be at least %d characters after trimming.',
                    self::MINIMUM_TERM_LENGTH,
                ));
            }
        });

        static::saved(fn () => Cache::forget(CacheKey::ModerationWatchlistTerms));
        static::deleted(fn () => Cache::forget(CacheKey::ModerationWatchlistTerms));
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'term',
                'note',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == accessors

    // == mutators

    /**
     * Terms are stored lowercased. Matching then lowercases the body once per
     * check instead of lowercasing every term on every check, and the unique
     * index rejects casing-only duplicates.
     *
     * @return Attribute<string, string>
     */
    protected function term(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => mb_strtolower(trim($value)),
        );
    }

    // == relations

    // == scopes
}
