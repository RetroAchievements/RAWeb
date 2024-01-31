<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

class DeleteAvatar
{
    public function __construct(
        private LinkLatestAvatar $linkLatestAvatarAction
    ) {
    }

    public function execute(User $user): void
    {
        /*
         * remove local entry
         */
        if ($user->hasMedia('avatar')) {
            $user->getMedia('avatar')->last()->delete();
        }

        /*
         * link the now latest avatar
         */
        $this->linkLatestAvatarAction->execute($user);
    }
}
