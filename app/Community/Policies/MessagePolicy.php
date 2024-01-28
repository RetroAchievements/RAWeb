<?php

declare(strict_types=1);

namespace App\Community\Policies;

use App\Community\Models\Message;
use App\Site\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return false;
    }

    public function view(User $user, Message $message): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Message $message): bool
    {
        return false;
    }

    public function delete(User $user, Message $message): bool
    {
        return false;
    }

    public function restore(User $user, Message $message): bool
    {
        return false;
    }

    public function forceDelete(User $user, Message $message): bool
    {
        return false;
    }
}
