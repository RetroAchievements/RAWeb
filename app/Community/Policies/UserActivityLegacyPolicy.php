<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserActivityLegacyPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }
}
