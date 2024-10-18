<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubscriptionPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return false;
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $user->is($subscription->user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->is($subscription->user);
    }

    public function delete(User $user, Subscription $subscription): bool
    {
        return $user->is($subscription->user);
    }

    public function restore(User $user, Subscription $subscription): bool
    {
        return $user->is($subscription->user);
    }

    public function forceDelete(User $user, Subscription $subscription): bool
    {
        return false;
    }
}
