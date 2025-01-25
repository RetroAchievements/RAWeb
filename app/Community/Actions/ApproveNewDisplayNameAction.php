<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\User;
use App\Models\UserUsername;

class ApproveNewDisplayNameAction
{
    public function execute(User $user, UserUsername $changeRequest): void
    {
        // Automatically mark conflicting requests as denied.
        UserUsername::where('username', $changeRequest->username)
            ->where('id', '!=', $changeRequest->id)
            ->whereNull('approved_at')
            ->whereNull('denied_at')
            ->update(['denied_at' => now()]);

        $changeRequest->update(['approved_at' => now()]);

        $user->display_name = $changeRequest->username;
        $user->save();

        sendDisplayNameChangeConfirmationEmail($user, $changeRequest->username);
    }
}
