<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database\Eloquent\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GameHash extends BaseModel
{
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

    public function getRouteKeyName(): string
    {
        return 'hash';
    }

    // == logging

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // == search

    public function toSearchableArray(): array
    {
        return $this->only([
            'id',
            'hash',
            'Name',
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

    // == mutators

    // == relations

    /**
     * @return BelongsToMany<AchievementSet>
     */
    public function achievementSets(): BelongsToMany
    {
        return $this->belongsToMany(AchievementSet::class, 'achievement_set_game_hashes')
            ->withPivot('compatible')
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
