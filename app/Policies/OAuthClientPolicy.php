<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OAuthClientPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return $user !== null && $this->isFeatureEnabled();
    }

    public function view(User $user, OAuthClient $client): bool
    {
        return $this->isFeatureEnabled() && $client->owner()->is($user);
    }

    /**
     * The per-user application quota is deliberately not enforced here. Being at
     * the quota is a validation concern, so a user at their limit still holds
     * this ability and receives a validation error rather than a 403.
     */
    public function create(User $user): bool
    {
        return $this->isFeatureEnabled() && $user->hasVerifiedEmail();
    }

    public function update(User $user, OAuthClient $client): bool
    {
        return $this->view($user, $client);
    }

    public function delete(User $user, OAuthClient $client): bool
    {
        return $this->view($user, $client);
    }

    private function isFeatureEnabled(): bool
    {
        return (bool) config('feature.oauth');
    }
}
