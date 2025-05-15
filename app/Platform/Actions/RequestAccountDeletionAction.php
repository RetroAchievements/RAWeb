<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Mail\RequestAccountDeleteMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class RequestAccountDeletionAction
{
    public function execute(User $user): bool
    {
        // already requested account deletion
        if ($user->DeleteRequested) {
            return false;
        }

        // If the user has elevated permissions, drop them to Registered
        $currentPermissions = $user->getAttribute('Permissions');
        if ($currentPermissions > Permissions::Registered) {
            $user->setAttribute('Permissions', Permissions::Registered);

            $user->roles()->detach();
            $user->permissions()->detach();

            updateClaimsForPermissionChange($user, Permissions::Registered, $currentPermissions);
        }

        $user->DeleteRequested = Carbon::now();
        $user->saveQuietly();

        addArticleComment('Server', ArticleType::UserModeration, $user->ID,
            $user->display_name . ' requested account deletion'
        );

        Mail::to($user)->queue(new RequestAccountDeleteMail($user));

        return true;
    }
}
