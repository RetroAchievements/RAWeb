<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\MessageThread;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessageThreadPolicy
{
    use HandlesAuthorization;

    public function view(User $user, MessageThread $message): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MessageThread $message): bool
    {
        return false;
    }

    public function delete(User $user, MessageThread $message): bool
    {
        return true;
    }

    public function restore(User $user, MessageThread $message): bool
    {
        return false;
    }

    public function forceDelete(User $user, MessageThread $message): bool
    {
        return false;
    }
}
