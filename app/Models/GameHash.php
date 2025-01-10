<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\GameHashFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GameHash extends BaseModel
{
    /** @use HasFactory<GameHashFactory> */
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    // TODO drop User in favor of user_id
    // TODO migrate functionality from md5 to hash. ensure md5 retains external md5 values and hash is the primary reference for game detection
    protected $table = 'game_hashes';

    protected $fillable = [
        'description',
        'game_id',
        'hash',
        'labels',
        'md5',
        'compatibility',
        'name',
        'system_id',
        'user_id',
        'source',
        'patch_url',
    ];

    protected $casts = [
        'file_names' => 'json',
        'regions' => 'json',
    ];

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'labels',
                'md5',
                'patch_url',
                'source',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'hash',
            'name',
            'md5',
            'description',
        ]);
    }

    public function shouldBeSearchable(): bool
    {
        // TODO return true;
        return false;
    }

    // == actions

    public function addFileName(string $gameHashName): self
    {
        if (empty($gameHashName)) {
            return $this;
        }
        $fileNames = $this->file_names;
        if (empty($fileNames)) {
            $fileNames = Arr::wrap($gameHashName);
        } else {
            if (!in_array($gameHashName, $fileNames)) {
                $fileNames[] = $gameHashName;
            }
        }
        $this->file_names = $fileNames;

        return $this;
    }

    public function addRegion(string $region): self
    {
        if (empty($region)) {
            return $this;
        }
        $regions = $this->regions;
        $region = mb_strtolower($region);
        if (empty($regions)) {
            $regions = Arr::wrap($region);
        } else {
            if (!in_array($region, $regions)) {
                $regions[] = $region;
            }
        }
        $this->regions = $regions;

        return $this;
    }

    // == accessors

    /**
     * Check if the game hash is related to a multi-disc game.
     * These hashes nearly always have the word "disk" or "disc" in their label.
     * This is temporary until we fully support game_hash_sets.
     */
    public function isMultiDiscGameHash(): bool
    {
        $name = Str::lower($this->name);
        $patterns = [
            'disk ', // avoid matching words like "diskworld"
            'disk)', // match phrases like "bonus disk)"
            'disc ', // avoid matching words like "discovery" or "discs of tron"
            'disc)', // match phrases like "bonus disc)"
            'side a',
            'side b',
        ];

        foreach ($patterns as $pattern) {
            if (Str::contains($name, $pattern)) {
                return true;
            }
        }

        return false;
    }

    // == mutators

    // == relations

    /**
     * @return BelongsToMany<AchievementSet>
     */
    public function incompatibleAchievementSets(): BelongsToMany
    {
        return $this->belongsToMany(AchievementSet::class, 'achievement_set_incompatible_game_hashes')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Game, GameHash>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'game_id');
    }

    /**
     * @return BelongsToMany<GameHashSet>
     */
    public function gameHashSets(): BelongsToMany
    {
        return $this->belongsToMany(GameHashSet::class, 'game_hash_set_hashes')
            ->using(GameHashSetHash::class)
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<System, GameHash>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    /**
     * @return BelongsTo<User, GameHash>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'ID');
    }

    // == scopes
}
