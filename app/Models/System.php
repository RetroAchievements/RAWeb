<?php

declare(strict_types=1);

namespace App\Models;

use App\Community\Concerns\DiscussedInForum;
use App\Support\Database\Eloquent\BaseModel;
use App\Support\Database\Eloquent\BasePivot;
use Database\Factories\SystemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class System extends BaseModel implements HasMedia
{
    use CausesActivity;
    use HasFactory;
    use InteractsWithMedia;
    use LogsActivity {
        LogsActivity::activities as auditLog;
    }
    use Searchable;
    use SoftDeletes;
    use DiscussedInForum;

    // TODO rename Console table to systems
    // TODO rename ID column to id
    // TODO rename Name column to name
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

    // == constants

    public const Arduboy = 71;
    public const WASM4 = 72;
    public const Uzebox = 80;
    public const Hubs = 100;
    public const Events = 101;

    public static function getHomebrewSystems(): array
    {
        return [System::Arduboy, System::WASM4, System::Uzebox];
    }

    public static function getNonGameSystems(): array
    {
        return [System::Hubs, System::Events];
    }

    public static function isGameSystem(int $type): bool
    {
        return $type != System::Hubs && $type != System::Events;
    }

    public static function isHomebrewSystem(int $type): bool
    {
        return in_array($type, self::getHomebrewSystems());
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
                    ->fit(Manipulations::FIT_MAX, 500, 500);
                $this->addMediaConversion('32')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Manipulations::FIT_MAX, 64, 64);
                $this->addMediaConversion('64')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Manipulations::FIT_MAX, 64, 64);
            });
    }

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'name',
            'name_full',
            'name_short',
            'manufacturer',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        if (!$this->active) {
            return false;
        }

        // TODO return true;
        return false;
    }

    // == accessors

    public function getCanonicalUrlAttribute(): string
    {
        return route('system.show', [$this, $this->getSlugAttribute()]);
    }

    public function getPermalinkAttribute(): string
    {
        return route('system.show', $this);
    }

    public function getSlugAttribute(): string
    {
        return ($this->name_full ?? $this->name) ? '-' . Str::slug($this->name_full ?? $this->name) : '';
    }

    public function getAchievementsLinkAttribute(): string
    {
        return route('system.achievements', [$this->id, $this->getSlugAttribute()]);
    }

    public function getGamesLinkAttribute(): string
    {
        return route('system.game.index', [$this->id, $this->getSlugAttribute()]);
    }

    public function getIconUrlAttribute(): string
    {
        return asset('assets/images/system/' . Str::kebab(str_replace('/', '', Str::lower($this->name_short))) . '.png');
    }
    
    // TODO remove after rename

    public function getIdAttribute(): ?int
    {
        return $this->attributes['ID'] ?? null;
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
            ->withTimestamps();
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
}
