<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OAuthClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Passport\Client;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OAuthClient extends Client
{
    /** @use HasFactory<OAuthClientFactory> */
    use HasFactory;
    use LogsActivity;

    protected static function newFactory(): OAuthClientFactory
    {
        return OAuthClientFactory::new();
    }

    protected static function booted(): void
    {
        // The audit log's subject_id column is an integer, so it cannot hold
        // this model's UUID primary key. Each client gets a numeric key
        // at creation time and audit rows are linked through those instead.
        static::creating(function (OAuthClient $client) {
            $client->numeric_id ??= ((int) static::query()->max('numeric_id')) + 1;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'revoked',
                'redirect_uris',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->subject_id = $this->numeric_id;
    }

    // == accessors

    // == mutators

    // == relations

    /**
     * @return MorphMany<Model, $this>
     */
    public function auditLog(): MorphMany
    {
        return $this->morphMany(ActivitylogServiceProvider::determineActivityModel(), 'subject', null, null, 'numeric_id');
    }

    /**
     * @return HasMany<OAuthGrant, $this>
     */
    public function grants(): HasMany
    {
        return $this->hasMany(OAuthGrant::class, 'client_id');
    }

    /**
     * @return Builder<static>
     */
    public function resolveRouteBindingQuery($query, $value, $field = null): Builder
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)->where('revoked', false);
    }

    // == scopes

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('revoked', false);
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->whereMorphedTo('owner', $user);
    }
}
