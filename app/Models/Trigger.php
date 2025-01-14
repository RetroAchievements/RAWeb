<?php

declare(strict_types=1);

namespace App\Models;

use App\Platform\Enums\TriggerableType;
use App\Support\Database\Eloquent\BaseModel;
use Database\Factories\TriggerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trigger extends BaseModel
{
    /** @use HasFactory<TriggerFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'triggerable_type',
        'triggerable_id',
        'user_id',
        'conditions',
        'version',
        'parent_id',
        'created_at', // TODO remove after initial sync
        'updated_at', // TODO remove after initial sync
    ];

    protected $casts = [
        'triggerable_type' => TriggerableType::class,
        'version' => 'integer',
    ];

    protected static function newFactory(): TriggerFactory
    {
        return TriggerFactory::new();
    }

    // == accessors

    public function getIsInitialVersionAttribute(): bool
    {
        return $this->parent_id === null;
    }

    public function getIsLatestVersionAttribute(): bool
    {
        return !$this->nextVersion()->exists();
    }

    // == mutators

    // == relations

    /**
     * @return HasOne<Trigger>
     */
    public function nextVersion(): HasOne
    {
        return $this->hasOne(Trigger::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Trigger, Trigger>
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(Trigger::class, 'parent_id');
    }

    /**
     * @return MorphTo<Model, Trigger>
     */
    public function triggerable(): MorphTo
    {
        return $this->morphTo();
    }

    // == scopes

    /**
     * @param Builder<Trigger> $query
     * @return Builder<Trigger>
     */
    public function scopeLatestVersion(Builder $query): Builder
    {
        return $query->whereNotExists(function ($query) {
            $query->from('triggers', 't2')
                ->whereColumn('t2.parent_id', 'triggers.id');
        });
    }

    /**
     * @param Builder<Trigger> $query
     * @return Builder<Trigger>
     */
    public function scopeInitialVersion(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @param Builder<Trigger> $query
     * @return Builder<Trigger>
     */
    public function scopeVersion(Builder $query, int $version): Builder
    {
        return $query->whereVersion($version);
    }

    /**
     * @param Builder<Trigger> $query
     * @return Builder<Trigger>
     */
    public function scopeOfType(Builder $query, TriggerableType $type): Builder
    {
        return $query->whereTriggerableType($type);
    }

    /**
     * @param Builder<Trigger> $query
     * @return Builder<Trigger>
     */
    public function scopeVersioned(Builder $query): Builder
    {
        return $query->whereNotNull('version');
    }

    /**
     * @param Builder<Trigger> $query
     * @return Builder<Trigger>
     */
    public function scopeUnversioned(Builder $query): Builder
    {
        return $query->whereNull('version');
    }
}
