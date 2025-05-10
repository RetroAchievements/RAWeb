<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\DiscussedInForum;
use App\Support\Database\Eloquent\BaseModel;
use App\Support\Database\Eloquent\BasePivot;
use App\Support\Routing\HasSelfHealingUrls;
use Database\Factories\SystemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class System extends BaseModel implements HasMedia
{
    /*
     * Community Traits
     */
    use DiscussedInForum;

    /*
     * Persistence Traits
     */
    /** @use HasFactory<SystemFactory> */
    use HasFactory;
    use SoftDeletes;

    /*
     * Behavioral Traits
     */
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }
    use CausesActivity;

    /*
     * Media Management Traits
     */
    use InteractsWithMedia;

    /*
     * Searching, Filtering, and Routing Traits
     */
    use HasSelfHealingUrls;

    // TODO rename Console table to systems
    // TODO rename ID column to id, remove getIdAttribute()
    // TODO rename Name column to name, remove getNameAttribute()
    // TODO rename Created column to created_at
    // TODO rename Updated column to updated_at
    // TODO: store aggregates?
    // $table->unsignedInteger('points_total')->nullable();
    // $table->unsignedInteger('points_weighted')->nullable();
    // $table->unsignedInteger('achievements_total')->nullable();
    // $table->unsignedInteger('achievements_published')->nullable();
    // $table->unsignedInteger('achievements_unpublished')->nullable();
    protected $table = 'Console';

    protected $primaryKey = 'ID';

    public const CREATED_AT = 'Created';
    public const UPDATED_AT = 'Updated';

    protected static function newFactory(): SystemFactory
    {
        return SystemFactory::new();
    }

    protected function getSlugSourceField(): string
    {
        return 'name';
    }

    protected $fillable = [
        'Name',
        'name_full',
        'name_short',
        'manufacturer',
        'order_column',
        'active',
    ];

    protected $visible = [
        'ID',
        'Name',
        'name_full',
        'name_short',
        'manufacturer',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // == constants

    public const Arduboy = 71;
    public const WASM4 = 72;
    public const Uzebox = 80;
    public const Hubs = 100;
    public const Events = 101;
    public const Standalones = 102;

    public static function getHomebrewSystems(): array
    {
        return [System::Arduboy, System::WASM4, System::Uzebox];
    }

    public static function getNonGameSystems(): array
    {
        return [System::Hubs, System::Events];
    }

    // TODO add attribute accessor ($system->is_game_system)
    public static function isGameSystem(int $systemId): bool
    {
        return $systemId !== System::Hubs && $systemId !== System::Events;
    }

    // TODO add attribute accessor ($system->is_homebrew_system)
    public static function isHomebrewSystem(int $systemId): bool
    {
        return in_array($systemId, self::getHomebrewSystems());
    }

    // audit activity log

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'Name',
                'name_full',
                'name_short',
                'manufacturer',
                'active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == media

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('2xl')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Fit::Max, 500, 500);
                $this->addMediaConversion('32')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Fit::Max, 64, 64);
                $this->addMediaConversion('64')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Fit::Max, 64, 64);
            });
    }

    // == accessors

    public function getPermalinkAttribute(): string
    {
        return route('system.show', $this);
    }

    public function getIconUrlAttribute(): string
    {
        return asset('assets/images/system/' . Str::kebab(str_replace('/', '', Str::lower($this->name_short))) . '.png');
    }

    // TODO remove after rename
    public function getIdAttribute(): int
    {
        return $this->attributes['ID'];
    }

    // TODO remove after rename
    public function getNameAttribute(): string
    {
        return $this->attributes['Name'] ?? '';
    }

    // == mutators

    // == relations

    /**
     * @return BelongsToMany<Emulator>
     */
    public function emulators(): BelongsToMany
    {
        return $this->belongsToMany(Emulator::class, 'system_emulators')
            ->using(BasePivot::class)
            ->withTimestamps('created_at', 'updated_at');
    }

    /**
     * @return HasMany<Game>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'ConsoleID');
    }

    /**
     * TODO: store achievements_published and achievements_total on games to be easily filterable
     *
     * @return HasMany<Game>
     */
    public function achievementGames(): HasMany
    {
        return $this->hasMany(Game::class)->where('achievements_published', '>', 0);
    }

    /**
     * @return HasManyThrough<Achievement>
     */
    public function achievements(): HasManyThrough
    {
        return $this->hasManyThrough(Achievement::class, Game::class);
    }

    // == scopes

    /**
     * @param Builder<System> $query
     * @return Builder<System>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @param Builder<System> $query
     * @return Builder<System>
     */
    public function scopeHasAchievements(Builder $query): Builder
    {
        $query->withCount('achievements');

        return $query->having('achievements_count', '>', '0');
    }

    /**
     * @param Builder<System> $query
     * @return Builder<System>
     */
    public function scopeHasGames(Builder $query): Builder
    {
        $query->withCount('games');

        return $query->having('games_count', '>', '0');
    }

    /**
     * @param Builder<System> $query
     * @return Builder<System>
     */
    public function scopeGameSystems(Builder $query): Builder
    {
        return $query->whereNotIn('id', $this->getNonGameSystems());
    }
}
