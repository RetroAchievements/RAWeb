<?php

declare(strict_types=1);

namespace App\Site\Concerns;

use App\Site\Models\UserConnection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait HasAccount
{
    public static function bootHasAccount(): void
    {
    }

    // == accessors

    public function getAssignableRolesAttribute(): Collection
    {
        $hasRoles = $this->roles->pluck('name');

        return collect(config('roles'))->filter(fn ($role) => $hasRoles->contains($role['name']))->pluck('assign')->flatten()->filter();
    }

    // == relations

    public function connections(): HasMany
    {
        return $this->hasMany(UserConnection::class);
    }

    public function connection(string $provider): HasMany
    {
        return $this->connections()->where('provider', $provider);
    }
}
