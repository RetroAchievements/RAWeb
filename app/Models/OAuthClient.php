<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\OAuthClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Passport\Client;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class OAuthClient extends Client
{
    /** @use HasFactory<OAuthClientFactory> */
    use HasFactory;

    use LogsActivity {
        LogsActivity::activities as auditLog;
    }

    protected static function newFactory(): OAuthClientFactory
    {
        return OAuthClientFactory::new();
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

    // == accessors

    // == mutators

    // == relations

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
